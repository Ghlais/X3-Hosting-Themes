<?php

declare(strict_types=1);

namespace Pterodactyl\BlueprintFramework\Extensions\xthreegames;

use DOMDocument;
use DOMElement;
use DomainException;

final class GameConfig
{
    public const PATHS = [
        'paper' => '/server.properties',
        'samp' => '/server.cfg',
        'mta' => '/mods/deathmatch/mtaserver.conf',
    ];

    public const FIELDS = [
        'paper' => [
            'difficulty' => ['options' => ['peaceful', 'easy', 'normal', 'hard']],
            'gamemode' => ['options' => ['survival', 'creative', 'adventure', 'spectator']],
            'max-players' => ['min' => 1, 'max' => 10000],
            'view-distance' => ['min' => 3, 'max' => 32],
            'simulation-distance' => ['min' => 3, 'max' => 32],
            'pvp' => ['options' => ['true', 'false']],
        ],
        'samp' => [
            'hostname' => ['text' => 63],
            'maxplayers' => ['min' => 1, 'max' => 1000],
            'gamemode0' => ['text' => 100],
        ],
        'mta' => [
            'servername' => ['text' => 96],
            'maxplayers' => ['min' => 1, 'max' => 1024],
        ],
    ];

    public static function game(string $egg): ?string
    {
        $egg = preg_replace('/[^a-z0-9]/', '', strtolower($egg));

        return match ($egg) {
            'paper', 'papermc', 'minecraftpaper', 'paperminecraft' => 'paper',
            'samp', 'sanandreasmultiplayer' => 'samp',
            'mta', 'mtasa', 'multitheftauto', 'multitheftautosanandreas' => 'mta',
            default => null,
        };
    }

    public static function values(string $game, string $raw): array
    {
        return self::edit($game, $raw, [])[0];
    }

    public static function normalized(string $game, string $key, mixed $value): string
    {
        $field = self::FIELDS[$game][$key] ?? null;
        if ($field === null || !is_string($value) || strlen($value) > 384 || !mb_check_encoding($value, 'UTF-8')) {
            throw new DomainException('INVALID_FIELD');
        }
        if (isset($field['options'])) {
            if (!in_array($value, $field['options'], true)) {
                throw new DomainException('INVALID_FIELD');
            }
        } elseif (isset($field['min'])) {
            if (!preg_match('/\A[0-9]{1,5}\z/D', $value) || (int) $value < $field['min'] || (int) $value > $field['max']) {
                throw new DomainException('INVALID_FIELD');
            }
            return (string) (int) $value;
        } elseif ($key === 'gamemode0') {
            if (!preg_match('/\A[A-Za-z0-9_-]{1,64} [1-9][0-9]{0,3}\z/D', $value)) {
                throw new DomainException('INVALID_GAMEMODE');
            }
        } elseif (!preg_match('/\A[\pL\pM\pN\pP\pS\p{Zs}]+\z/uD', $value) || mb_strlen($value) > $field['text'] || str_contains($value, '//')) {
            throw new DomainException('INVALID_FIELD');
        }

        return $value;
    }

    public static function edit(string $game, string $raw, array $changes): array
    {
        if ($raw === '' || str_contains($raw, "\0")) {
            throw new DomainException('INVALID_CONFIG');
        }
        if ($game === 'mta') {
            return self::xml($raw, $changes);
        }

        $separator = $game === 'paper' ? '=' : ' ';
        $eol = str_contains($raw, "\r\n") ? "\r\n" : "\n";
        $pieces = preg_split('/(\r\n|\n|\r)/', $raw, -1, PREG_SPLIT_DELIM_CAPTURE);
        $keys = array_fill_keys(array_keys(self::FIELDS[$game]), true);
        $values = [];
        foreach ($pieces as $i => $line) {
            if ($i % 2 !== 0 || trim($line) === '' || preg_match('/^\s*(?:[#!]|\/\/)/', $line)) {
                continue;
            }
            if ($game === 'paper' && (preg_match('/(?<!\\\\)(?:\\\\\\\\)*\\\\$/', $line) || str_contains(preg_split('/[\s=:]/', ltrim($line), 2)[0], '\\'))) {
                throw new DomainException('COMPLEX_PROPERTIES');
            }
            $pattern = $game === 'paper' ? '/^(\s*)([a-zA-Z0-9.-]+)(?:\s*[=:]\s*|\s+)(.*)$/' : '/^(\s*)([a-zA-Z0-9_-]+)\s+(.*)$/';
            if (!preg_match($pattern, $line, $match)) {
                if ($game === 'paper' && isset($keys[trim($line)])) {
                    throw new DomainException('INVALID_CONFIG');
                }
                continue;
            }
            $key = $game === 'samp' ? strtolower($match[2]) : $match[2];
            if (!isset($keys[$key])) {
                continue;
            }
            if (array_key_exists($key, $values)) {
                throw new DomainException('DUPLICATE_FIELD');
            }
            $values[$key] = trim($match[3]);
            if (array_key_exists($key, $changes)) {
                $pieces[$i] = $match[1] . $match[2] . $separator . $changes[$key];
            }
        }
        $output = implode('', $pieces);
        foreach (array_diff_key($changes, $values) as $key => $value) {
            if (!preg_match('/[\r\n]$/', $output)) {
                $output .= $eol;
            }
            $output .= $key . $separator . $value . $eol;
        }

        return [$values, $output];
    }

    private static function xml(string $raw, array $changes): array
    {
        if (preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $raw) || !mb_check_encoding($raw, 'UTF-8')) {
            throw new DomainException('INVALID_CONFIG');
        }
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->preserveWhiteSpace = true;
        if (!@$xml->loadXML($raw, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING) || $xml->doctype !== null
            || $xml->documentElement?->nodeName !== 'config'
            || !in_array(strtoupper($xml->encoding ?? 'UTF-8'), ['UTF-8', 'UTF8'], true)) {
            throw new DomainException('INVALID_CONFIG');
        }

        $values = [];
        foreach ($xml->documentElement->childNodes as $node) {
            if (!$node instanceof DOMElement || !isset(self::FIELDS['mta'][$node->nodeName])) {
                continue;
            }
            $key = $node->nodeName;
            if (array_key_exists($key, $values) || $node->getElementsByTagName('*')->length > 0) {
                throw new DomainException('DUPLICATE_FIELD');
            }
            $values[$key] = trim($node->textContent);
            if (isset($changes[$key])) {
                $node->textContent = $changes[$key];
            }
        }
        if (array_diff_key($changes, $values)) {
            throw new DomainException('MISSING_XML_FIELD');
        }

        return [$values, $changes === [] ? $raw : $xml->saveXML()];
    }
}
