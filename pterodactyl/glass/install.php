<?php

declare(strict_types=1);

const PANEL_VERSIONS = ['1.15.1', '1.15.2'];
const WRAPPER = 'resources/views/templates/wrapper.blade.php';
const HOOK = "        {{-- X3 Rackline Studio: managed include --}}\n        @include('x3-rackline.head')\n";
const PAYLOAD = [
    'public/x3-rackline/v1/mark.svg',
    'public/x3-rackline/v1/rackline.svg',
    'public/x3-rackline/v1/glass-1.1.1.css',
    'public/x3-rackline/v1/glass-1.1.1.js',
    'resources/views/x3-rackline/head.blade.php',
];

function checkedPath(string $base, string $relative): string
{
    foreach (explode('/', $relative) as $part) {
        if ($part === '' || $part === '.' || $part === '..' || str_contains($part, '\\')) {
            throw new RuntimeException("Invalid relative path: $relative");
        }
        $base .= '/' . $part;
        if (is_link($base)) {
            throw new RuntimeException("Refusing symbolic link: $base");
        }
    }
    return $base;
}

function readFileContents(string $path): string
{
    if (!is_file($path) || !is_readable($path) || ($content = file_get_contents($path)) === false) {
        throw new RuntimeException("Cannot read $path");
    }
    return $content;
}

function digest(?string $bytes): ?string
{
    return $bytes === null ? null : hash('sha256', $bytes);
}

function snapshot(string $path): array
{
    if (!file_exists($path)) {
        return ['bytes' => null, 'mode' => 0644, 'uid' => null, 'gid' => null];
    }
    $bytes = readFileContents($path);
    return ['bytes' => $bytes, 'mode' => fileperms($path) & 0777, 'uid' => fileowner($path), 'gid' => filegroup($path)];
}

function directory(string $path, int $mode = 0755): void
{
    if (!is_dir($path) && !mkdir($path, $mode, true) && !is_dir($path)) {
        throw new RuntimeException("Cannot create $path");
    }
}

function writeState(string $path, array $state): void
{
    if ($state['bytes'] === null) {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException("Cannot remove $path");
        }
        return;
    }
    $parent = dirname($path);
    directory($parent);
    $temp = tempnam($parent, '.x3-');
    if ($temp === false) {
        throw new RuntimeException("Cannot stage $path");
    }
    try {
        if (dirname($temp) !== realpath($parent) || file_put_contents($temp, $state['bytes']) !== strlen($state['bytes'])) {
            throw new RuntimeException("Cannot write $path");
        }
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('posix_geteuid') && posix_geteuid() === 0) {
            if (($state['uid'] !== null && !chown($temp, $state['uid'])) ||
                ($state['gid'] !== null && !chgrp($temp, $state['gid']))) {
                throw new RuntimeException("Cannot preserve ownership of $path");
            }
        }
        if (!chmod($temp, $state['mode']) || !rename($temp, $path)) {
            throw new RuntimeException("Cannot replace $path");
        }
    } finally {
        if (is_file($temp)) {
            unlink($temp);
        }
    }
}

function clearViews(string $root): void
{
    $process = proc_open([PHP_BINARY, 'artisan', 'view:clear', '--no-interaction'], [STDIN, STDOUT, STDERR], $pipes, $root);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Artisan view:clear failed.');
    }
}

function buildChanges(string $root, string $version, array $current, array $payload, string|false $rollback): array
{
    if ($rollback !== false) {
        $base = checkedPath($root, 'storage/app/x3-rackline-backups');
        $folder = basename($rollback);
        if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{10}$/D', $folder) || realpath($rollback) !== realpath($base) . DIRECTORY_SEPARATOR . $folder) {
            throw new RuntimeException('Use the backup path printed by this installer.');
        }
        $backup = checkedPath($base, $folder);
        $record = json_decode(readFileContents(checkedPath($backup, 'manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        if (($record['root'] ?? null) !== $root || ($record['version'] ?? null) !== $version || !is_array($record['files'] ?? null)) {
            throw new RuntimeException('Backup belongs to a different panel.');
        }
        $changes = [];
        foreach (array_reverse($record['files'], true) as $relative => $entry) {
            if (!isset($current[$relative]) || !is_array($entry) || !array_key_exists('before', $entry) || !array_key_exists('after', $entry)) {
                throw new RuntimeException('Invalid backup manifest.');
            }
            $state = $current[$relative];
            $before = $entry['before'] === null ? null : readFileContents(checkedPath($backup, 'files/' . $relative));
            if (digest($before) !== $entry['before']) {
                throw new RuntimeException("Backup checksum failed: $relative");
            }
            if ($state['bytes'] === $before) {
                continue;
            }
            if (digest($state['bytes']) !== $entry['after']) {
                throw new RuntimeException("File changed after install: $relative. Keep your changes and review the backup manually.");
            }
            $changes[$relative] = ['bytes' => $before, 'mode' => (int) $entry['mode'] & 0777, 'uid' => $entry['uid'], 'gid' => $entry['gid']];
        }
        return $changes;
    }

    $wrapper = $current[WRAPPER]['bytes'];
    if ($wrapper === null || substr_count($wrapper, '</head>') !== 1) {
        throw new RuntimeException('Unexpected panel wrapper. A custom integration is required for this layout.');
    }
    $normalized = str_replace("\r\n", "\n", $wrapper);
    if (substr_count($normalized, 'x3-rackline.head') !== substr_count($normalized, HOOK) || substr_count($normalized, HOOK) > 1) {
        throw new RuntimeException('An unrecognized X3 include already exists.');
    }
    if (!str_contains($normalized, HOOK) && !str_contains($wrapper, "\$asset->js('main.js')")) {
        throw new RuntimeException('Unexpected panel wrapper. A custom integration is required for this layout.');
    }
    $patch = str_contains($wrapper, "\r\n") ? str_replace("\n", "\r\n", HOOK) : HOOK;
    $payload[WRAPPER] = str_contains($normalized, HOOK) ? $wrapper : preg_replace('/^([ \t]*)<\/head>/m', $patch . '$1</head>', $wrapper, 1, $count);
    if (!is_string($payload[WRAPPER]) || ($count ?? 1) !== 1) {
        throw new RuntimeException('Could not locate the closing head line.');
    }
    $changes = [];
    foreach ($payload as $relative => $bytes) {
        if ($current[$relative]['bytes'] !== $bytes) {
            $changes[$relative] = array_replace($current[$relative], ['bytes' => $bytes]);
        }
    }
    return $changes;
}

function backupChanges(string $root, string $version, array $current, array $changes): string
{
    $directory = checkedPath($root, 'storage/app/x3-rackline-backups/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(5)));
    directory($directory, 0700);
    $manifest = ['root' => $root, 'version' => $version, 'files' => []];
    foreach ($changes as $relative => $next) {
        $old = $current[$relative];
        $manifest['files'][$relative] = [
            'before' => digest($old['bytes']), 'after' => digest($next['bytes']),
            'mode' => $old['mode'], 'uid' => $old['uid'], 'gid' => $old['gid'],
        ];
        if ($old['bytes'] !== null) {
            writeState(checkedPath($directory, 'files/' . $relative), ['bytes' => $old['bytes'], 'mode' => 0600, 'uid' => null, 'gid' => null]);
        }
    }
    writeState(checkedPath($directory, 'manifest.json'), [
        'bytes' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        'mode' => 0600, 'uid' => null, 'gid' => null,
    ]);
    return $directory;
}

function main(array $args): void
{
    $rootInput = '/var/www/pterodactyl';
    $apply = $check = false;
    $rollback = false;
    foreach (array_slice($args, 1) as $arg) {
        if ($arg === '--apply') {
            $apply = true;
        } elseif ($arg === '--check') {
            $check = true;
        } elseif (str_starts_with($arg, '--root=')) {
            $rootInput = substr($arg, 7);
        } elseif (str_starts_with($arg, '--rollback=')) {
            $rollback = substr($arg, 11);
        } else {
            throw new RuntimeException('Usage: php install.php [--root=/var/www/pterodactyl] [--apply | --check | --rollback=/backup --apply]');
        }
    }
    if ($check && ($apply || $rollback !== false)) {
        throw new RuntimeException('--check is a standalone verification option.');
    }
    $root = realpath($rootInput);
    if ($root === false || !is_file(checkedPath($root, 'artisan')) || !is_file(checkedPath($root, 'public/assets/manifest.json')) ||
        !is_dir(checkedPath($root, 'storage/app'))) {
        throw new RuntimeException('Use the root of an installed Pterodactyl panel.');
    }
    $config = readFileContents(checkedPath($root, 'config/app.php'));
    if (!preg_match("/['\"]version['\"]\\s*=>\\s*['\"](\\d+\\.\\d+\\.\\d+)['\"]/", $config, $match) || !in_array($match[1], PANEL_VERSIONS, true)) {
        throw new RuntimeException('This release supports Pterodactyl Panel ' . implode(' and ', PANEL_VERSIONS) . ' only.');
    }
    $version = $match[1];
    $source = realpath(__DIR__);
    if ($source === $root || str_starts_with($source, $root . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Extract the release in /tmp, outside the panel.');
    }
    $payload = [];
    $hashes = json_decode(readFileContents(__DIR__ . '/FILES-SHA256.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (PAYLOAD as $relative) {
        $bytes = readFileContents(checkedPath(__DIR__, 'payload/' . $relative));
        if (($hashes[$relative] ?? null) !== digest($bytes)) {
            throw new RuntimeException("Payload checksum failed: $relative");
        }
        $payload[$relative] = $bytes;
    }

    $lock = null;
    try {
        if ($apply) {
            $lock = fopen(checkedPath($root, 'storage/app/.x3-rackline.lock'), 'c');
            if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('An X3 install or rollback is already running.');
            }
        }
        $current = [];
        foreach ([...PAYLOAD, WRAPPER] as $relative) {
            $current[$relative] = snapshot(checkedPath($root, $relative));
        }
        $changes = buildChanges($root, $version, $current, $payload, $rollback);
        if ($check && $changes !== []) {
            throw new RuntimeException('Installation differs from this release: ' . implode(', ', array_keys($changes)));
        }
        if ($changes === []) {
            echo "X3 Rackline Glass: no file changes needed on Pterodactyl $version.\n";
            return;
        }
        if (!$apply) {
            echo "Preflight passed. Files to change:\n - " . implode("\n - ", array_keys($changes)) . "\nRun again with --apply.\n";
            return;
        }
        $backup = backupChanges($root, $version, $current, $changes);
        echo "Backup: $backup\n";
        $applied = [];
        try {
            foreach ($changes as $relative => $state) {
                writeState(checkedPath($root, $relative), $state);
                $applied[] = $relative;
            }
            clearViews($root);
        } catch (Throwable $error) {
            $restoreFailures = [];
            foreach (array_reverse($applied) as $relative) {
                try {
                    writeState(checkedPath($root, $relative), $current[$relative]);
                } catch (Throwable $restoreError) {
                    $restoreFailures[] = $relative;
                }
            }
            $result = $restoreFailures === [] ? ' Previous files restored.' : ' Manual restoration required: ' . implode(', ', $restoreFailures);
            throw new RuntimeException($error->getMessage() . $result . ' Backup: ' . $backup);
        }
        echo ($rollback === false ? 'Installed X3 Rackline Glass v1.1.1.' : 'Rollback completed.') . "\n";
        echo 'Rollback: php ' . escapeshellarg(__FILE__) . ' --root=' . escapeshellarg($root) . ' --rollback=' . escapeshellarg($backup) . " --apply\n";
    } finally {
        if (is_resource($lock)) {
            fclose($lock);
        }
    }
}

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    main($argv);
} catch (Throwable $error) {
    fwrite(STDERR, 'X3 Rackline: ' . $error->getMessage() . "\n");
    exit(1);
}
