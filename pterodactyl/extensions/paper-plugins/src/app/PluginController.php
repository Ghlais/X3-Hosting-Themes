<?php

declare(strict_types=1);

namespace Pterodactyl\BlueprintFramework\Extensions\xthreepaper;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Throwable;

final class PluginController
{
    public function __construct(private Modrinth $modrinth, private DaemonFileRepository $files)
    {
    }

    public function status(Request $request, string $serverUuid): JsonResponse
    {
        $server = $this->authorize($request, $serverUuid);

        return new JsonResponse([
            'version' => $this->detectedVersion($server),
            'canInstall' => $this->canInstall($request, $server),
        ]);
    }

    public function search(Request $request, string $serverUuid): JsonResponse
    {
        $server = $this->authorize($request, $serverUuid);
        $query = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'between:1,50']]);

        return new JsonResponse($this->modrinth->search(
            $query['q'] ?? '',
            $this->gameVersion($request, $server),
            (int) ($query['page'] ?? 1),
        ));
    }

    public function versions(Request $request, string $serverUuid, string $projectId): JsonResponse
    {
        $server = $this->authorize($request, $serverUuid);

        return new JsonResponse($this->modrinth->versions($projectId, $this->gameVersion($request, $server)));
    }

    public function install(Request $request, string $serverUuid): JsonResponse
    {
        $server = $this->authorize($request, $serverUuid);
        abort_unless($this->canInstall($request, $server), 403, 'File create and update permissions are required.');
        $input = $request->validate([
            'projectId' => ['required', 'string', 'regex:' . Modrinth::ID],
            'versionId' => ['required', 'string', 'regex:' . Modrinth::ID],
            'acceptDependencies' => ['required', 'boolean'],
        ]);
        $game = $this->gameVersion($request, $server);
        $name = 'x3-modrinth-' . $input['projectId'] . '.jar';

        $result = Cache::lock('x3paper:install:' . $server->uuid, 180)->get(function () use ($request, $server, $input, $game, $name) {
            $files = $this->files->setServer($server);
            try {
                $this->checkDestination($files, $name);
                $release = $this->modrinth->resolve($input['projectId'], $input['versionId'], $game);
                abort_if($release['dependencies'] && !$input['acceptDependencies'], 422, 'Confirm the required dependencies before installing.');

                $temp = tmpfile();
                abort_if($temp === false, 500, 'Unable to stage the plugin.');
                $stage = '.x3paper-' . bin2hex(random_bytes(16)) . '.part';
                $attempted = false;
                $file = $release['file'];
                try {
                    $this->modrinth->download($file, $temp);

                    // Recheck after download in case access or server settings changed.
                    $current = $this->authorize($request, $server->uuid);
                    abort_unless($this->canInstall($request, $current), 403, 'File permissions changed during installation.');
                    $this->gameVersion($request, $current);
                    $files->setServer($current);
                    $this->checkDestination($files, $name);

                    $attempted = true;
                    $files->getHttpClient()->post('/api/servers/' . $current->uuid . '/files/write', [
                        'query' => ['file' => '/plugins/' . $stage],
                        'headers' => ['Content-Type' => 'application/octet-stream'],
                        'body' => $temp,
                        'connect_timeout' => 4,
                        'timeout' => 20,
                        'allow_redirects' => false,
                    ]);
                    $uploaded = $this->entry($files->getDirectory('/plugins'), $stage);
                    abort_unless($this->matches($uploaded, $file['size']), 502, 'Wings could not verify the staged upload.');

                    // Check again immediately before the native Wings move.
                    $this->checkDestination($files, $name);
                    $files->renameFiles('/plugins', [['from' => $stage, 'to' => $name]]);
                    $listing = $files->getDirectory('/plugins');
                    abort_unless(
                        $this->matches($this->entry($listing, $name), $file['size']) && $this->entry($listing, $stage) === null,
                        502,
                        'Wings could not verify the installed file. Check the plugins folder before retrying.',
                    );
                    $attempted = false;
                } finally {
                    if (is_resource($temp)) {
                        fclose($temp);
                    }
                    if ($attempted) {
                        try {
                            $files->deleteFiles('/plugins', [$stage]);
                        } catch (Throwable $exception) {
                            logger()->warning('X3 Paper could not clean its staging file.', ['server' => $server->uuid]);
                        }
                    }
                }
            } catch (TransferException|DaemonConnectionException $exception) {
                $cause = $exception instanceof DaemonConnectionException ? $exception->getPrevious() : $exception;
                if ($cause instanceof RequestException && $cause->getResponse()?->getStatusCode() === 400
                    && str_contains((string) $cause->getResponse()->getBody(), 'destination already exists')) {
                    abort(409, 'This project already has a managed JAR in the plugins folder.');
                }
                abort(502, 'Wings could not complete the installation. Check the plugins folder and try again.');
            }

            try {
                Activity::event('server:file.write')->subject($server)->property([
                    'file' => '/plugins/' . $name,
                    'source' => 'Modrinth',
                    'version_id' => $input['versionId'],
                ])->log();
            } catch (Throwable $exception) {
                logger()->warning('X3 Paper could not record the completed installation.', ['server' => $server->uuid]);
            }

            return ['file' => '/plugins/' . $name, 'restartRequired' => true];
        });
        abort_if($result === false, 409, 'An installation is already in progress for this server.');

        return new JsonResponse($result, 201);
    }

    private function authorize(Request $request, string $serverUuid): Server
    {
        $user = $request->user();
        abort_unless($user !== null, 401);
        $server = Server::query()->where('uuid', $serverUuid)->firstOrFail();
        abort_unless($user->can(Permission::ACTION_FILE_READ, $server), 404);
        $server->validateCurrentState();
        abort_if($server->status !== null, 409, 'The server is not ready.');

        $egg = preg_replace('/[^a-z0-9]/', '', strtolower($server->egg->name ?? ''));
        abort_unless(
            in_array($egg, ['paper', 'papermc', 'minecraftpaper', 'paperminecraft'], true),
            409,
            'Paper Plugins is available only for a supported Paper egg.',
        );

        return $server;
    }

    private function canInstall(Request $request, Server $server): bool
    {
        $user = $request->user();

        return $user->can(Permission::ACTION_FILE_CREATE, $server)
            && $user->can(Permission::ACTION_FILE_UPDATE, $server);
    }

    private function detectedVersion(Server $server): ?string
    {
        foreach ($server->variables as $variable) {
            $value = $variable->server_value ?? $variable->default_value;
            if (in_array($variable->env_variable, ['MINECRAFT_VERSION', 'MC_VERSION'], true)
                && is_string($value) && preg_match(Modrinth::GAME_VERSION, $value)) {
                return $value;
            }
        }

        return null;
    }

    private function gameVersion(Request $request, Server $server): string
    {
        $input = $request->validate(['game' => ['required', 'string', 'regex:' . Modrinth::GAME_VERSION]]);
        $detected = $this->detectedVersion($server);
        abort_if($detected !== null && $detected !== $input['game'], 422, 'Choose the Minecraft version configured for this server.');

        return $input['game'];
    }

    private function entry(array $listing, string $name): ?array
    {
        foreach ($listing as $file) {
            if (($file['name'] ?? null) === $name) {
                return $file;
            }
        }

        return null;
    }

    private function matches(?array $file, int $size): bool
    {
        return ($file['file'] ?? null) === true && ($file['symlink'] ?? null) === false
            && ($file['size'] ?? null) === $size;
    }

    private function checkDestination(DaemonFileRepository $files, string $name): void
    {
        $plugins = $this->entry($files->getDirectory('/'), 'plugins');
        abort_unless(
            ($plugins['directory'] ?? false) === true && ($plugins['symlink'] ?? null) === false,
            409,
            'The plugins folder is missing or is a symlink. Start Paper once to create its plugins folder.',
        );
        abort_if($this->entry($files->getDirectory('/plugins'), $name) !== null, 409, 'This project already has a managed JAR in the plugins folder.');
    }
}
