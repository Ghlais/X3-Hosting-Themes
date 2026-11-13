<?php

declare(strict_types=1);

namespace Pterodactyl\BlueprintFramework\Extensions\xthreegames;

use DomainException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Throwable;

final class GameController
{
    private const STATE = '/plugins/X3Players/panel-state.json';
    private const ACTIONS = ['allow', 'unallow', 'ban', 'pardon', 'kick', 'whitelist-on', 'whitelist-off'];
    private const PLAYER = '/\A[A-Za-z0-9_]{1,16}\z/D';

    public function __construct(private DaemonFileRepository $files, private DaemonCommandRepository $commands)
    {
    }

    public function access(Request $request, string $uuid): array
    {
        [$server, $game, $owner, $subuser] = $this->context($request, $uuid);
        $actor = $request->user();
        $runtime = RuntimeInspector::runtime($server->egg->name);

        return [
            'game' => $game,
            'settings' => $game !== null && $actor->can(Permission::ACTION_FILE_READ_CONTENT, $server),
            'players' => $game === 'paper' && ($owner || $this->hasGrant($subuser)),
            'delegate' => $game === 'paper' && $owner,
            'runtime' => $actor->can(Permission::ACTION_STARTUP_READ, $server) ? $runtime : null,
        ];
    }

    public function runtime(Request $request, string $uuid, RuntimeInspector $inspector): array
    {
        [$server] = $this->context($request, $uuid);
        $actor = $request->user();
        $runtime = RuntimeInspector::runtime($server->egg->name);
        abort_unless($runtime !== null && $actor->can(Permission::ACTION_STARTUP_READ, $server), 403);

        return $inspector->inspect($server, $runtime, $actor->can(Permission::ACTION_FILE_READ, $server));
    }

    public function settings(Request $request, string $uuid): array
    {
        [$server, $game] = $this->context($request, $uuid);
        $this->requireSettings($request, $server, $game);
        $path = GameConfig::PATHS[$game];
        $raw = $this->read($server, $path);
        $managed = $this->managed($server, $path);
        $values = $this->config(fn () => GameConfig::values($game, $raw));
        $fields = [];
        foreach (GameConfig::FIELDS[$game] as $key => $rule) {
            $value = $values[$key] ?? '';
            $fields[] = ['key' => $key, 'value' => mb_scrub($value, 'UTF-8'), 'managed' => in_array($key, $managed, true), ...$rule];
        }

        return ['path' => $path, 'revision' => hash('sha256', $raw), 'canSave' => $this->canSave($request, $server), 'fields' => $fields];
    }

    public function save(Request $request, string $uuid): array
    {
        [$server, $game] = $this->context($request, $uuid);
        $this->requireSettings($request, $server, $game);
        abort_unless($this->canSave($request, $server), 403, 'FILE_PERMISSION');
        $input = $request->validate([
            'revision' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'],
            'changes' => ['required', 'array', 'min:1', 'max:6'],
        ]);
        $path = GameConfig::PATHS[$game];
        $changes = [];
        foreach ($input['changes'] as $key => $value) {
            abort_unless(is_string($key), 422, 'INVALID_FIELD');
            $changes[$key] = $this->config(fn () => GameConfig::normalized($game, $key, $value));
        }

        $result = Cache::lock('xthreegames:config:' . $uuid, 120)->get(function () use ($request, $uuid, $game, $path, $input, $changes) {
            [$server, $current] = $this->context($request, $uuid);
            abort_unless($current === $game && $this->canSave($request, $server)
                && $request->user()->can(Permission::ACTION_FILE_READ_CONTENT, $server), 403, 'FILE_PERMISSION');
            abort_if(array_intersect(array_keys($changes), $this->managed($server, $path)), 422, 'EGG_MANAGED');
            $raw = $this->read($server, $path);
            abort_unless(hash_equals(hash('sha256', $raw), $input['revision']), 409, 'STALE_CONFIG');
            [$before, $next] = $this->config(fn () => GameConfig::edit($game, $raw, $changes));
            if (array_intersect_assoc($changes, $before) === $changes || $next === $raw) {
                return ['saved' => false, 'restartRequired' => false];
            }
            abort_if(strlen($next) > 262144, 422, 'FILE_LIMIT');
            if (isset($changes['gamemode0'])) {
                $amx = explode(' ', $changes['gamemode0'], 2)[0] . '.amx';
                $listing = $this->wings(fn () => $this->files->setServer($server)->getDirectory('/gamemodes'));
                $files = array_filter($listing, fn ($row) => ($row['file'] ?? false) === true && !($row['symlink'] ?? false));
                abort_unless(in_array($amx, array_column($files, 'name'), true), 422, 'GAMEMODE_MISSING');
            }
            $backup = $path . '.x3-previous';
            abort_unless($this->pathExists($server, $path), 409, 'STALE_CONFIG');
            $this->pathExists($server, $backup);
            $this->wings(fn () => $this->files->setServer($server)->putContent($backup, $raw));
            abort_unless($this->read($server, $backup) === $raw, 502, 'BACKUP_FAILED');
            abort_unless($this->read($server, $path) === $raw, 409, 'STALE_CONFIG');
            $this->wings(fn () => $this->files->setServer($server)->putContent($path, $next));
            abort_unless($this->read($server, $path) === $next, 502, 'SAVE_UNVERIFIED');
            $this->audit($server, 'server:file.write', ['file' => $path, 'source' => 'X3 Game Tools', 'fields' => array_keys($changes)]);

            return ['saved' => true, 'restartRequired' => true, 'backup' => $backup];
        });
        abort_if($result === false, 409, 'BUSY');

        return $result;
    }

    public function players(Request $request, string $uuid): array
    {
        $server = $this->requirePlayers($request, $uuid);
        return $this->snapshot($server);
    }

    public function moderate(Request $request, string $uuid): array
    {
        $server = $this->requirePlayers($request, $uuid);
        $input = $request->validate([
            'action' => ['required', 'string', Rule::in(self::ACTIONS)],
            'player' => ['nullable', 'string', 'regex:' . self::PLAYER],
        ]);
        $action = $input['action'];
        $player = $input['player'] ?? null;
        $toggle = in_array($action, ['whitelist-on', 'whitelist-off'], true);
        abort_if($toggle === ($player !== null), 422, 'PLAYER_REQUIRED');
        $state = $this->snapshot($server);
        abort_unless($state['ready'], 409, 'BRIDGE_OFFLINE');
        $server = $this->requirePlayers($request, $uuid);
        $id = bin2hex(random_bytes(16));
        $command = 'x3players:x3players ' . $id . ' ' . $action . ($player !== null ? ' ' . $player : '');
        $this->wings(fn () => $this->commands->setServer($server)->send($command));
        $this->audit($server, 'server:x3.player-action', ['action' => $action, 'player' => $player, 'receipt' => $id]);

        return ['receipt' => $id];
    }

    public function moderators(Request $request, string $uuid): array
    {
        [$server, $game, $owner] = $this->context($request, $uuid);
        abort_unless($game === 'paper' && $owner, 403);
        $subusers = $server->subusers()->with('user:id,username')->orderBy('id')->get();
        $grants = DB::table('xthreegames_moderators')->whereIn('subuser_id', $subusers->modelKeys())->pluck('subuser_id')->all();

        return $subusers->map(fn (Subuser $subuser) => [
            'id' => $subuser->id, 'username' => $subuser->user->username,
            'enabled' => in_array((int) $subuser->id, array_map('intval', $grants), true),
        ])->all();
    }

    public function grant(Request $request, string $uuid, string $subuser): array
    {
        [$server, $game, $owner] = $this->context($request, $uuid);
        abort_unless($game === 'paper' && $owner, 403);
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        DB::transaction(function () use ($server, $subuser, $enabled) {
            $row = $server->subusers()->whereKey($subuser)->lockForUpdate()->firstOrFail();
            $table = DB::table('xthreegames_moderators');
            if ($enabled) {
                $table->insertOrIgnore(['subuser_id' => $row->id]);
            } else {
                $table->where('subuser_id', $row->id)->delete();
            }
        });
        $this->audit($server, 'server:x3.player-access', ['subuser' => (int) $subuser, 'enabled' => (bool) $enabled]);

        return ['enabled' => (bool) $enabled];
    }

    private function context(Request $request, string $uuid): array
    {
        $actor = $request->user();
        abort_unless($actor !== null, 401);
        $server = Server::query()->where('uuid', $uuid)->with('egg')->firstOrFail();
        $owner = $actor->root_admin || (int) $server->owner_id === (int) $actor->id;
        $subuser = $owner ? null : $server->subusers()->where('user_id', $actor->id)->first();
        abort_unless($owner || $subuser !== null, 404);
        $server->validateCurrentState();
        abort_if($server->status !== null, 409, 'SERVER_NOT_READY');

        return [$server, GameConfig::game($server->egg->name), $owner, $subuser];
    }

    private function requireSettings(Request $request, Server $server, ?string $game): void
    {
        abort_unless($game !== null && $request->user()->can(Permission::ACTION_FILE_READ_CONTENT, $server), 403, 'FILE_PERMISSION');
    }

    private function canSave(Request $request, Server $server): bool
    {
        return $request->user()->can(Permission::ACTION_FILE_CREATE, $server)
            && $request->user()->can(Permission::ACTION_FILE_UPDATE, $server);
    }

    private function hasGrant(?Subuser $subuser): bool
    {
        return $subuser !== null && DB::table('xthreegames_moderators')->where('subuser_id', $subuser->id)->exists();
    }

    private function requirePlayers(Request $request, string $uuid): Server
    {
        [$server, $game, $owner, $subuser] = $this->context($request, $uuid);
        abort_unless($game === 'paper' && ($owner || $this->hasGrant($subuser)), 403, 'MODERATOR_PERMISSION');

        return $server;
    }

    private function managed(Server $server, string $path): array
    {
        $spec = $server->egg->inherit_config_files;
        $rules = is_string($spec) ? json_decode($spec, true) : $spec;
        $keys = [];
        foreach (is_array($rules) ? $rules : [] as $file => $rule) {
            if (is_string($file) && is_array($rule) && '/' . ltrim($file, './') === $path) {
                array_push($keys, ...array_map('strtolower', array_keys($rule['find'] ?? [])));
            }
        }

        return $keys;
    }

    private function snapshot(Server $server): array
    {
        $raw = $this->read($server, self::STATE, true);
        $state = $raw === null ? null : json_decode($raw, true, 10);
        $time = $state['time'] ?? 0;
        $ready = ($state['v'] ?? null) === 1 && ($state['ready'] ?? null) === true
            && is_int($time) && abs(time() - $time) <= 30;
        $names = static function (mixed $list): array {
            return array_values(array_filter(is_array($list) ? array_slice($list, 0, 2500) : [],
                static fn ($name) => is_string($name) && preg_match(self::PLAYER, $name)));
        };
        $receipts = [];
        foreach (array_slice((array) ($state['receipts'] ?? []), -64, null, true) as $id => $ok) {
            if (is_string($id) && preg_match('/\A[a-f0-9]{32}\z/D', $id) && is_bool($ok)) {
                $receipts[$id] = $ok;
            }
        }

        return [
            'ready' => $ready, 'updated' => $ready ? $time : null,
            'enabled' => $ready && ($state['enabled'] ?? null) === true,
            'online' => $ready ? $names($state['online'] ?? []) : [],
            'allowlist' => $ready ? $names($state['allowlist'] ?? []) : [],
            'banned' => $ready ? $names($state['banned'] ?? []) : [],
            'receipts' => (object) $receipts,
        ];
    }

    private function read(Server $server, string $path, bool $optional = false): ?string
    {
        try {
            $response = $this->files->setServer($server)->getHttpClient()->get('/api/servers/' . $server->uuid . '/files/contents', [
                'query' => ['file' => $path], 'stream' => true, 'timeout' => 8, 'read_timeout' => 5, 'allow_redirects' => false,
            ]);
        } catch (TransferException $e) {
            $response = $e instanceof \GuzzleHttp\Exception\RequestException ? $e->getResponse() : null;
            $status = $response?->getStatusCode();
            $response?->getBody()->close();
            if ($status === 404 || ($status === 500 && !$this->pathExists($server, $path))) {
                abort_unless($optional, 404, 'CONFIG_MISSING');
                return null;
            }
            abort(502, 'WINGS_UNAVAILABLE');
        }
        $body = $response->getBody();
        $raw = '';
        $deadline = microtime(true) + 8;
        try {
            while (!$body->eof() && strlen($raw) <= 262144) {
                abort_if(microtime(true) > $deadline, 502, 'WINGS_UNAVAILABLE');
                $part = $body->read(32768);
                if ($part === '') {
                    abort_unless($body->eof(), 502, 'WINGS_UNAVAILABLE');
                    break;
                }
                $raw .= $part;
            }
        } catch (\RuntimeException $e) {
            abort(502, 'WINGS_UNAVAILABLE');
        } finally {
            $body->close();
        }
        abort_if(strlen($raw) > 262144, 422, 'FILE_LIMIT');

        return $raw;
    }

    private function pathExists(Server $server, string $path): bool
    {
        $parts = explode('/', trim($path, '/'));
        $directory = '';
        foreach ($parts as $index => $part) {
            $rows = $this->wings(fn () => $this->files->setServer($server)->getDirectory($directory === '' ? '/' : $directory));
            $byName = array_column($rows, null, 'name');
            $entry = $byName[$part] ?? null;
            if ($entry === null) {
                return false;
            }
            $last = $index === count($parts) - 1;
            abort_if(($entry['symlink'] ?? false) || ($entry['file'] ?? null) !== $last, 422, 'UNSAFE_FILE');
            $directory .= '/' . $part;
        }

        return true;
    }

    private function config(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (DomainException $e) {
            abort(422, $e->getMessage());
        }
    }

    private function wings(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (TransferException|DaemonConnectionException $e) {
            abort(502, 'WINGS_UNAVAILABLE');
        }
    }

    private function audit(Server $server, string $event, array $data): void
    {
        try {
            Activity::event($event)->subject($server)->property($data)->log();
        } catch (Throwable $e) {
            logger()->warning('X3 Game Tools activity log unavailable', ['server' => $server->uuid, 'event' => $event]);
        }
    }
}
