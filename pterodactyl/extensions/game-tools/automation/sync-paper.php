<?php
declare(strict_types=1);

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

const X3_JAR = 'X3Players.jar';
const X3_SHA = 'bbfc6c164b840e7315e3f9a8012d26b49a87e9763ac24bf5ab2958ab297e6f28';
const X3_PANEL = '/var/www/pterodactyl';

if ($argc !== 2 || !in_array($argv[1], ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php sync-paper.php --check|--apply\n");
    exit(2);
}
$jar = file_get_contents(__DIR__ . '/' . X3_JAR);
if (!is_string($jar) || !hash_equals(X3_SHA, hash('sha256', $jar))) {
    fwrite(STDERR, "X3Players.jar failed local integrity validation\n");
    exit(2);
}
require X3_PANEL . '/vendor/autoload.php';
$app = require X3_PANEL . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function x3File(?array $entry): bool
{
    return ($entry['file'] ?? false) === true && !($entry['symlink'] ?? false);
}

function x3Matches(Server $server, DaemonFileRepository $files, array $entry, string $path, int $size): bool
{
    if (!x3File($entry) || (int) ($entry['size'] ?? -1) !== $size) {
        return false;
    }
    $response = $files->getHttpClient()->get('/api/servers/' . $server->uuid . '/files/contents', [
        'query' => ['file' => $path], 'stream' => true, 'timeout' => 8, 'read_timeout' => 5, 'allow_redirects' => false,
    ]);
    $body = $response->getBody();
    $raw = '';
    $deadline = microtime(true) + 8;
    try {
        while (!$body->eof() && strlen($raw) <= $size && microtime(true) < $deadline) {
            $part = $body->read($size + 1 - strlen($raw));
            if ($part === '') {
                break;
            }
            $raw .= $part;
        }
    } finally {
        $body->close();
    }
    return strlen($raw) === $size && hash_equals(X3_SHA, hash('sha256', $raw));
}

function x3Sync(Server $server, DaemonFileRepository $files, string $jar, bool $apply): string
{
    $vars = $server->variables()->whereIn('env_variable', ['MINECRAFT_VERSION', 'SERVER_JARFILE'])->get()
        ->keyBy('env_variable');
    $v = $vars->get('MINECRAFT_VERSION');
    $j = $vars->get('SERVER_JARFILE');
    $version = trim((string) ($v->server_value ?? $v->default_value ?? ''));
    $serverJar = (string) ($j->server_value ?? $j->default_value ?? 'server.jar');
    if ($version !== '' && strcasecmp($version, 'latest') !== 0) {
        if (!preg_match('/\A([0-9]+)\.([0-9]+)(?:\.[0-9]+)?\z/D', $version, $match)
            || (int) $match[1] === 0 || ((int) $match[1] === 1 && (int) $match[2] < 20)) {
            return 'unsupported_version';
        }
    }
    if (!preg_match('/\A[A-Za-z0-9._-]+\.jar\z/D', $serverJar)
        || preg_match('/(?:^|\s)(?:-P|--plugins)(?:[=\s]|$)/', $server->startup)) {
        return 'custom_layout';
    }
    $root = array_column($files->getDirectory('/'), null, 'name');
    if (!x3File($root[$serverJar] ?? null)) {
        return 'waiting_for_game_jar';
    }
    $plugins = $root['plugins'] ?? null;
    if ($plugins !== null && (($plugins['file'] ?? null) !== false || ($plugins['symlink'] ?? false))) {
        return 'custom_layout';
    }
    $items = $plugins === null ? [] : array_column($files->getDirectory('/plugins'), null, 'name');
    if (isset($items[X3_JAR])) {
        return x3Matches($server, $files, $items[X3_JAR], '/plugins/' . X3_JAR, strlen($jar)) ? 'verified' : 'existing_different';
    }
    foreach (array_keys($items) as $item) {
        if (preg_match('/\Ax3players(?:[-_.][A-Za-z0-9._-]*)?\.jar\z/iD', $item)) {
            return 'existing_alternate';
        }
    }
    if (!$apply) {
        return 'ready_to_copy';
    }
    if ($plugins === null) {
        $files->createDirectory('plugins', '/');
    }
    $part = '.x3players-' . bin2hex(random_bytes(10)) . '.part';
    $tempPath = '/plugins/' . $part;
    try {
        $files->putContent($tempPath, $jar);
        $listing = array_column($files->getDirectory('/plugins'), null, 'name');
        if (!isset($listing[$part]) || !x3Matches($server, $files, $listing[$part], $tempPath, strlen($jar))) {
            return 'staging_verification_failed';
        }
        foreach (array_keys($listing) as $item) {
            if (preg_match('/\Ax3players(?:[-_.][A-Za-z0-9._-]*)?\.jar\z/iD', $item)) {
                return 'existing_alternate';
            }
        }
        $files->renameFiles('/plugins', [['from' => $part, 'to' => X3_JAR]]);
        $listing = array_column($files->getDirectory('/plugins'), null, 'name');
        return isset($listing[X3_JAR]) && x3Matches($server, $files, $listing[X3_JAR], '/plugins/' . X3_JAR, strlen($jar))
            ? 'installed' : 'post_copy_verification_failed';
    } finally {
        try {
            $files->deleteFiles('/plugins', [$part]);
        } catch (Throwable) {
            // A successful move already removes the staging entry.
        }
    }
}

$eligible = Egg::query()->whereRaw("LOWER(name) IN ('paper', 'papermc', 'paper mc', 'minecraft paper', 'paper minecraft')")
    ->whereHas('nest', fn ($query) => $query->whereRaw("LOWER(name) = 'minecraft'"))->pluck('id');
$summary = [];
$failed = false;
Server::query()->whereIn('egg_id', $eligible)->whereNull('status')->orderBy('id')->chunkById(25, function ($servers) use ($app, $jar, $argv, &$summary, &$failed) {
    foreach ($servers as $server) {
        try {
            $result = x3Sync($server, $app->make(DaemonFileRepository::class)->setServer($server), $jar, $argv[1] === '--apply');
        } catch (Throwable $exception) {
            $result = 'wings_error:' . (new ReflectionClass($exception))->getShortName();
        }
        $summary[$result] = ($summary[$result] ?? 0) + 1;
        if (!in_array($result, ['verified', 'installed'], true) || $argv[1] === '--check') {
            echo "server={$server->id} $result\n";
        }
        $failed = $failed || str_starts_with($result, 'wings_error:') || str_ends_with($result, '_failed');
    }
});
echo json_encode($summary, JSON_THROW_ON_ERROR), PHP_EOL;
exit($failed ? 1 : 0);
