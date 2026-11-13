<?php

declare(strict_types=1);

namespace Pterodactyl\BlueprintFramework\Extensions\xthreegames;

use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

final class RuntimeInspector
{
    public static function runtime(string $egg): ?string
    {
        return match (preg_replace('/[^a-z0-9]/', '', strtolower($egg))) {
            'nodejsgeneric', 'genericnodejs' => 'node',
            'pythongeneric', 'genericpython' => 'python',
            default => null,
        };
    }

    public function __construct(private DaemonFileRepository $files)
    {
    }

    public function inspect(Server $server, string $runtime, bool $canList): array
    {
        $entryKey = $runtime === 'node' ? 'MAIN_FILE' : 'PY_FILE';
        $keys = [$entryKey, 'AUTO_UPDATE'];
        if ($runtime === 'python') {
            $keys[] = 'REQUIREMENTS_FILE';
        }
        $variables = $server->variables()->where('user_viewable', true)->whereIn('env_variable', $keys)->get()
            ->mapWithKeys(fn ($variable) => [$variable->env_variable => (string) ($variable->server_value ?? $variable->default_value)]);
        $paths = [
            'entry' => $this->safePath($variables->get($entryKey)),
            'manifest' => $runtime === 'node' ? 'package.json' : $this->safePath($variables->get('REQUIREMENTS_FILE')),
        ];
        if ($runtime === 'node') {
            $paths['lockfile'] = 'package-lock.json';
        }

        $directories = [];
        $files = [];
        foreach ($paths as $kind => $path) {
            $files[] = ['kind' => $kind, 'path' => $path, 'state' => match (true) {
                $path === null => 'unconfigured',
                !$canList => 'unavailable',
                default => $this->check($server, $path, $directories),
            }];
        }

        return [
            'runtime' => $runtime,
            'autoUpdate' => match ($variables->get('AUTO_UPDATE')) {
                '1', 'true' => true,
                '0', 'false' => false,
                default => null,
            },
            'files' => $files,
        ];
    }

    private function safePath(?string $path): ?string
    {
        if ($path === null || strlen($path) > 240) {
            return null;
        }
        $path = preg_replace('~^\./~', '', $path, 1);
        if (!preg_match('~\A[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*\z~D', $path)
            || array_intersect(explode('/', $path), ['.', '..'])) {
            return null;
        }

        return $path;
    }

    private function check(Server $server, string $path, array &$directories): string
    {
        $parts = explode('/', $path);
        $directory = '/';
        foreach ($parts as $index => $part) {
            if (!array_key_exists($directory, $directories)) {
                try {
                    $directories[$directory] = array_column($this->files->setServer($server)->getDirectory($directory), null, 'name');
                } catch (DaemonConnectionException|TransferException) {
                    $directories[$directory] = null;
                }
            }
            $listing = $directories[$directory];
            if ($listing === null) {
                return 'unavailable';
            }
            $entry = $listing[$part] ?? null;
            $last = $index === count($parts) - 1;
            if ($entry === null || ($entry['file'] ?? null) !== $last) {
                return 'missing';
            }
            if ($entry['symlink'] ?? false) {
                return 'unavailable';
            }
            $directory = rtrim($directory, '/') . '/' . $part;
        }

        return 'present';
    }
}
