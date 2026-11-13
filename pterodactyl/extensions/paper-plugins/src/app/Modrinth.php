<?php

declare(strict_types=1);

namespace Pterodactyl\BlueprintFramework\Extensions\xthreepaper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use ZipArchive;

final class Modrinth
{
    private const API = 'https://api.modrinth.com/v2';
    private const CDN = 'https://cdn.modrinth.com';
    private const MAX_BYTES = 32 * 1024 * 1024;
    public const ID = '/\A[A-Za-z0-9]{1,32}\z/D';
    public const GAME_VERSION = '/\A[0-9]{1,3}\.[0-9]{1,3}(?:\.[0-9]{1,3})?\z/D';

    public function search(string $query, string $game, int $page): array
    {
        $limit = 12;
        $response = $this->cached('/search', [
            'query' => $query,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'index' => $query === '' ? 'downloads' : 'relevance',
            'facets' => json_encode([['project_type:plugin'], ['categories:paper'], ['versions:' . $game]]),
        ]);
        $results = [];
        foreach ($response['hits'] ?? [] as $hit) {
            if (!is_array($hit) || !self::validId($hit['project_id'] ?? null)) {
                continue;
            }

            $results[] = [
                'id' => $hit['project_id'],
                'title' => (string) ($hit['title'] ?? ''),
                'author' => (string) ($hit['author'] ?? ''),
                'description' => (string) ($hit['description'] ?? ''),
                'downloads' => (int) ($hit['downloads'] ?? 0),
            ];
        }

        return [
            'results' => $results,
            'hasNext' => $page < 50 && ($response['total_hits'] ?? 0) > $page * $limit,
        ];
    }

    public function versions(string $projectId, string $game): array
    {
        $versions = $this->cached('/project/' . $projectId . '/version', [
            'loaders' => '["paper"]',
            'game_versions' => json_encode([$game]),
        ]);
        $result = [];
        foreach ($versions as $version) {
            if (!is_array($version) || !self::compatible($version, $projectId, $game)
                || self::jar($version) === null) {
                continue;
            }

            $result[] = [
                'id' => $version['id'],
                'name' => (string) ($version['version_number'] ?? $version['id']),
                'channel' => (string) ($version['version_type'] ?? ''),
                'dependencies' => self::dependencies($version),
            ];

            if (count($result) === 25) {
                break;
            }
        }

        return $result;
    }

    public function resolve(string $projectId, string $versionId, string $game): array
    {
        // Fetch fresh metadata before installing in case a listing has been removed.
        $version = $this->request('/version/' . $versionId);
        $file = self::compatible($version, $projectId, $game) && ($version['id'] ?? null) === $versionId
            ? self::jar($version)
            : null;
        abort_if($file === null, 422, 'This version has no eligible Paper plugin JAR for the selected Minecraft version.');

        return ['file' => $file, 'dependencies' => self::dependencies($version)];
    }

    public function download(array $file, $target): void
    {
        $body = null;
        try {
            $response = $this->http()
                ->timeout(12)
                ->withOptions(['stream' => true, 'read_timeout' => 4])
                ->get($file['url']);
            $body = $response->toPsrResponse()->getBody();
            if (!$response->ok()) {
                throw new RuntimeException('The CDN returned an unexpected status.');
            }
            $this->verifyStream($body, $target, $file);
        } catch (ConnectionException|RuntimeException $exception) {
            abort(502, 'The plugin download could not be verified. Try again later.');
        } finally {
            $body?->close();
        }
    }

    public function verifyStream(StreamInterface $body, $target, array $file): void
    {
        $size = 0;
        $digest = hash_init('sha512');
        $deadline = hrtime(true) + 12_000_000_000;
        while (!$body->eof()) {
            if (hrtime(true) > $deadline) {
                throw new RuntimeException('Download timed out.');
            }
            $chunk = $body->read(65536);
            $length = strlen($chunk);
            $size += $length;
            if ($length === 0 || $size > $file['size'] || fwrite($target, $chunk) !== $length) {
                throw new RuntimeException('Download stream failed.');
            }
            hash_update($digest, $chunk);
        }
        if ($size !== $file['size'] || !hash_equals($file['sha512'], hash_final($digest))) {
            throw new RuntimeException('Download integrity mismatch.');
        }
        if (!fflush($target) || !rewind($target)) {
            throw new RuntimeException('Could not read staged download.');
        }

        $zip = new ZipArchive();
        if ($zip->open(stream_get_meta_data($target)['uri'], ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Invalid JAR archive.');
        }
        try {
            if ($zip->locateName('plugin.yml') === false && $zip->locateName('paper-plugin.yml') === false) {
                throw new RuntimeException('Missing Paper or Bukkit plugin descriptor.');
            }
        } finally {
            $zip->close();
        }
    }

    public static function compatible(array $version, string $projectId, string $game): bool
    {
        return ($version['project_id'] ?? null) === $projectId
            && self::validId($version['id'] ?? null)
            && in_array($version['status'] ?? 'listed', ['listed', 'archived'], true)
            && in_array('paper', (array) ($version['loaders'] ?? []), true)
            && in_array($game, (array) ($version['game_versions'] ?? []), true);
    }

    public static function jar(array $version): ?array
    {
        $jars = [];
        foreach ($version['files'] ?? [] as $file) {
            if (!is_array($file) || !is_string($file['url'] ?? null) || !is_string($file['filename'] ?? null)
                || !preg_match('/\.jar\z/i', $file['filename'])
                || preg_match('~[\\\\/\x00-\x1f]~', $file['filename'])
                || !is_int($file['size'] ?? null) || $file['size'] < 1 || $file['size'] > self::MAX_BYTES
                || !is_string($file['hashes']['sha512'] ?? null)
                || !preg_match('/\A[0-9a-f]{128}\z/i', $file['hashes']['sha512'])) {
                continue;
            }

            $parts = parse_url($file['url']);
            $prefix = '/data/' . $version['project_id'] . '/versions/' . $version['id'] . '/';
            if (!is_array($parts) || array_diff(array_keys($parts), ['scheme', 'host', 'path'])
                || ($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'cdn.modrinth.com'
                || !str_starts_with($parts['path'] ?? '', $prefix)
                || rawurldecode(substr($parts['path'], strlen($prefix))) !== $file['filename']
                || !str_starts_with($file['url'], self::CDN . $prefix)) {
                continue;
            }

            $jars[] = [
                'url' => $file['url'],
                'size' => $file['size'],
                'sha512' => strtolower($file['hashes']['sha512']),
                'primary' => ($file['primary'] ?? null) === true,
            ];
        }

        $primary = array_values(array_filter($jars, static fn (array $file) => $file['primary']));
        if (count($primary) === 1) {
            return $primary[0];
        }

        return count($jars) === 1 ? $jars[0] : null;
    }

    private static function dependencies(array $version): array
    {
        $dependencies = [];
        foreach ($version['dependencies'] ?? [] as $dependency) {
            if (!is_array($dependency) || ($dependency['dependency_type'] ?? null) !== 'required') {
                continue;
            }
            $dependencies[] = [
                'projectId' => self::validId($dependency['project_id'] ?? null) ? $dependency['project_id'] : null,
                'versionId' => self::validId($dependency['version_id'] ?? null) ? $dependency['version_id'] : null,
                'fileName' => is_string($dependency['file_name'] ?? null) ? $dependency['file_name'] : null,
            ];
        }

        return $dependencies;
    }

    private static function validId(mixed $id): bool
    {
        return is_string($id) && preg_match(self::ID, $id) === 1;
    }

    private function http(): PendingRequest
    {
        return Http::withUserAgent('Ghlais/X3-Paper-Plugins/1.0.1 (https://x3-hosting.xyz/)')
            ->connectTimeout(4)
            ->timeout(8)
            ->withOptions(['allow_redirects' => false]);
    }

    private function cached(string $path, array $params): array
    {
        return Cache::remember(
            'x3paper:modrinth:' . hash('sha256', $path . '?' . http_build_query($params)),
            180,
            fn () => $this->request($path, $params),
        );
    }

    private function request(string $path, array $params = []): array
    {
        try {
            $response = $this->http()->acceptJson()->get(self::API . $path, $params);
        } catch (ConnectionException $exception) {
            abort(502, 'Modrinth is temporarily unavailable.');
        }
        abort_if($response->notFound(), 404, 'The project or version was not found on Modrinth.');
        abort_if($response->status() === 429, 503, 'Modrinth is busy. Try again in a minute.');
        abort_unless($response->ok() && is_array($response->json()), 502, 'Modrinth returned an invalid response.');

        return $response->json();
    }
}
