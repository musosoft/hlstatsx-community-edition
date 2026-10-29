<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 * A modern Laravel 13 rewrite of the HLStatsX:CE web frontend, preserving the original MySQL schema.
 *
 * A long lineage of open-source stats for Half-Life & Source engine games:
 *   HLstats (Simon Garner, 2001) -> HLstatsX (Tobias Oetzel, 2005)
 *   -> HLstatsX:CE (Nicholas Hastings, 2008) -> This rebase (Royal-Multi-Gamers, 2026)
 *
 * Perl daemon sourced from SnipeZilla/HLSTATS-2.
 *
 * Copyright (C) 2025-2026 Royal-Multi-Gamers
 * Licensed under the GNU General Public License v2.0
 * https://www.gnu.org/licenses/gpl-2.0.html
 *
 * https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SteamService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.steam.api_key');
    }

    public function getAvatar(string $steamId64): ?string
    {
        return Cache::remember("steam.avatar.{$steamId64}", 3600, function () use ($steamId64) {
            // Try Steam Web API first (requires key)
            if ($this->apiKey) {
                try {
                    $response = Http::timeout(5)
                        ->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                            'key'      => $this->apiKey,
                            'steamids' => $steamId64,
                        ]);
                    $players = $response->json('response.players', []);
                    if (!empty($players[0]['avatarmedium'])) {
                        return $players[0]['avatarmedium'];
                    }
                } catch (\Exception $e) {
                    // fall through to public XML
                }
            }

            // Fallback: public Steam profile XML (no key required)
            try {
                $xml = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'HLStatsX/1.0'])
                    ->get("https://steamcommunity.com/profiles/{$steamId64}/?xml=1")
                    ->body();
                $obj = @simplexml_load_string($xml);
                $url = (string) ($obj->avatarMedium ?? '');
                return $url ?: null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    public function getDisplayName(string $steamId64): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        return Cache::remember("steam.name.{$steamId64}", 3600, function () use ($steamId64) {
            try {
                $response = Http::timeout(5)
                    ->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                        'key'      => $this->apiKey,
                        'steamids' => $steamId64,
                    ]);

                $players = $response->json('response.players', []);
                return $players[0]['personaname'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    public function steamId32to64(string $steamId32): string
    {
        // Full format: STEAM_X:Y:Z
        if (preg_match('/^STEAM_\d+:(\d+):(\d+)$/', $steamId32, $m)) {
            return (string) (76561197960265728 + (int)$m[2] * 2 + (int)$m[1]);
        }

        // Short format stored in DB: Y:Z (e.g. "1:933242689")
        if (preg_match('/^(\d+):(\d+)$/', $steamId32, $m)) {
            return (string) (76561197960265728 + (int)$m[2] * 2 + (int)$m[1]);
        }

        return $steamId32;
    }

    /**
     * Normalize a raw uniqueId to STEAM_0:Y:Z display format.
     */
    public function formatSteamId(string $uniqueId): string
    {
        if (preg_match('/^STEAM_\d+:(\d+):(\d+)$/', $uniqueId)) {
            return $uniqueId;
        }
        if (preg_match('/^(\d+):(\d+)$/', $uniqueId)) {
            return 'STEAM_0:' . $uniqueId;
        }
        return $uniqueId;
    }
}
