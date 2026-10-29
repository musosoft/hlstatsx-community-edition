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

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Server;
use App\Services\StatsService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    public function __construct(
        private StatsService $stats,
        private ThemeService $theme,
    ) {}

    public function index()
    {
        $globalStats = $this->stats->getGlobalStats();

        $games = Game::visible()
            ->ordered()
            ->with(['servers' => fn($q) => $q->visible()])
            ->get();

        $gameCodes = $games->pluck('code')->all();

        // Batch: top player per game — 1 query instead of N
        $topPlayersByGame = DB::table('hlstats_Players')
            ->whereIn('game', $gameCodes)
            ->where('hideranking', 0)
            ->orderByDesc('skill')
            ->get(['playerId', 'lastName', 'country', 'flag', 'game'])
            ->unique('game')
            ->keyBy('game');

        // Batch: top clan per game — 1 query instead of N
        $topClansByGame = DB::table('hlstats_Clans as c')
            ->join('hlstats_Players as p', function ($join) {
                $join->on('p.clan', '=', 'c.clanId')
                     ->where('p.hideranking', '=', 0);
            })
            ->whereIn('c.game', $gameCodes)
            ->where('c.hidden', 0)
            ->groupBy('c.clanId', 'c.name', 'c.tag', 'c.game')
            ->selectRaw('c.clanId, c.name, c.tag, c.game, SUM(p.kills) AS total_kills')
            ->orderByDesc('total_kills')
            ->get()
            ->unique('game')
            ->keyBy('game');

        $games = $games->map(fn($game) => [
            'game'             => $game,
            'topPlayer'        => $topPlayersByGame[$game->code] ?? null,
            'topClan'          => $topClansByGame[$game->code] ?? null,
            'connectedPlayers' => $game->servers->sum('act_players'),
            'maxPlayers'       => $game->servers->sum('max_players'),
        ]);

        $serverMarkers = Server::visible()
            ->whereNotNull('lat')->where('lat', '!=', 0)
            ->whereNotNull('lng')->where('lng', '!=', 0)
            ->get(['serverId', 'name', 'address', 'port', 'publicaddress', 'lat', 'lng', 'last_event'])
            ->map(fn($s) => [
                'lat'     => (float)$s->lat,
                'lng'     => (float)$s->lng,
                'name'    => $s->name,
                'address' => $s->full_address,
                'online'  => $s->last_event >= now()->subMinutes(5)->timestamp,
            ])
            ->toArray();

        $playerMarkers = DB::table('hlstats_Players')
            ->where('hideranking', 0)
            ->whereNotNull('lat')->where('lat', '!=', 0)
            ->whereNotNull('lng')->where('lng', '!=', 0)
            ->orderByDesc('skill')
            ->limit(500)
            ->get(['lat', 'lng', 'lastName', 'country', 'game'])
            ->map(fn($p) => [
                'lat'     => (float)$p->lat,
                'lng'     => (float)$p->lng,
                'name'    => $p->lastName,
                'country' => $p->country,
            ])
            ->toArray();

        $activeTheme = $this->theme->getActive();
        $tileUrl     = $activeTheme['charts']['map-tiles'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

        $voiceServers = $this->getVoiceServers();

        return view('frontend.home', compact('globalStats', 'games', 'serverMarkers', 'playerMarkers', 'tileUrl', 'voiceServers'));
    }

    private function getVoiceServers(): array
    {
        return DB::table('hlstats_Servers_VoiceComm')
            ->orderBy('serverType')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => (object) array_merge((array) $s, $this->fetchVoiceSummaryStats($s)))
            ->toArray();
    }

    private function fetchVoiceSummaryStats(object $server): array
    {
        $default = ['channels' => null, 'slotsUsed' => null, 'slotsMax' => null, 'displayAddr' => null, 'inviteUrl' => null];

        // Use a home-specific key to avoid colliding with VoiceCommController's raw widget cache
        $cacheKey = 'home_vs_' . $server->serverId;

        try {
            if ((int) $server->serverType === 0) {
                // TeamSpeak 3 — TCP query, cached 2 min
                return Cache::remember($cacheKey, 120, function () use ($server) {
                    $sock = @fsockopen($server->addr, (int) $server->queryPort, $errno, $errstr, 3);
                    if (!$sock) {
                        return ['channels' => null, 'slotsUsed' => null, 'slotsMax' => null,
                                'displayAddr' => $server->addr . ':' . $server->UDPPort, 'inviteUrl' => null];
                    }
                    stream_set_timeout($sock, 3);
                    fgets($sock); fgets($sock);
                    fwrite($sock, "use port={$server->UDPPort}\n"); fgets($sock);
                    fwrite($sock, "serverinfo\n");   $si    = fgets($sock);
                    fwrite($sock, "channellist\n");  $cl    = fgets($sock);
                    fwrite($sock, "clientlist\n");   $clist = fgets($sock);
                    fwrite($sock, "quit\n");
                    fclose($sock);

                    preg_match('/virtualserver_maxclients=(\d+)/', $si, $m);
                    $max      = (int) ($m[1] ?? 0);
                    $channels = substr_count($cl, 'cid=');
                    preg_match_all('/client_type=(\d+)/', $clist, $tm);
                    $used = count(array_filter($tm[1] ?? [], fn($t) => $t === '0'));

                    return ['channels' => $channels, 'slotsUsed' => $used, 'slotsMax' => $max,
                            'displayAddr' => $server->addr . ':' . $server->UDPPort, 'inviteUrl' => null];
                });

            } elseif ((int) $server->serverType === 1) {
                // Steam Group — XML API, cached 5 min
                return Cache::remember($cacheKey, 300, function () use ($server) {
                    $isNumeric = ctype_digit((string) $server->addr);
                    $steamApiUrl = $isNumeric
                        ? 'https://steamcommunity.com/gid/' . $server->addr . '/memberslistxml/?xml=1'
                        : 'https://steamcommunity.com/groups/' . rawurlencode($server->addr) . '/memberslistxml/?xml=1';
                    $response = Http::timeout(5)->get($steamApiUrl);
                    if (!$response->successful()) {
                        $isNum = ctype_digit((string) $server->addr);
                        return ['channels' => null, 'slotsUsed' => null, 'slotsMax' => null,
                                'displayAddr' => ($isNum ? 'steamcommunity.com/gid/' : 'steamcommunity.com/groups/') . $server->addr, 'inviteUrl' => null];
                    }
                    $parsed = @simplexml_load_string($response->body());
                    $name   = $parsed ? (string) ($parsed->groupDetails->groupName ?? $server->name) : $server->name;
                    $online = $parsed ? (int) ($parsed->groupDetails->membersOnline ?? 0) : null;
                    $total  = $parsed ? (int) ($parsed->groupDetails->memberCount   ?? 0) : null;

                    $isNumeric = ctype_digit((string) $server->addr);
                    $steamPageUrl = $isNumeric
                        ? 'https://steamcommunity.com/gid/' . $server->addr
                        : 'https://steamcommunity.com/groups/' . $server->addr;
                    return ['channels' => null, 'slotsUsed' => $online, 'slotsMax' => $total,
                            'displayAddr' => $name,
                            'inviteUrl'   => $steamPageUrl];
                });

            } elseif ((int) $server->serverType === 2) {
                // Discord — widget API + invite API for member count, cached 1 min
                return Cache::remember($cacheKey, 60, function () use ($server) {
                    $inviteCode   = trim($server->password ?? '');
                    $channels     = null;
                    $onlineCount  = null;
                    $memberCount  = null;
                    $inviteUrl    = null;
                    $displayAddr  = null;

                    // 1. Try widget API (requires widget enabled in Discord server settings)
                    $widgetRes = Http::timeout(5)->get(
                        'https://discord.com/api/guilds/' . rawurlencode($server->addr) . '/widget.json'
                    );
                    if ($widgetRes->successful()) {
                        $w = $widgetRes->json();
                        if (!isset($w['code'])) {
                            $channels    = count($w['channels'] ?? []);
                            $onlineCount = (int) ($w['presence_count'] ?? 0);
                            $rawInvite   = $w['instant_invite'] ?? null;
                            if ($rawInvite) {
                                $inviteUrl   = $rawInvite;
                                $displayAddr = preg_replace('#https?://discord\.gg/#i', 'discord.gg/', $rawInvite);
                                // Extract code from widget invite if no manual code stored
                                if (!$inviteCode) {
                                    preg_match('#discord\.gg/([^/?]+)#i', $rawInvite, $m);
                                    $inviteCode = $m[1] ?? '';
                                }
                            }
                        }
                    }

                    // 2. Always try invite API to get total member count
                    if ($inviteCode) {
                        $inviteRes = Http::timeout(5)->get(
                            'https://discord.com/api/v9/invites/' . rawurlencode($inviteCode) . '?with_counts=true'
                        );
                        if ($inviteRes->successful()) {
                            $d = $inviteRes->json();
                            if (!isset($d['message'])) { // not an error
                                $memberCount = isset($d['approximate_member_count'])
                                    ? (int) $d['approximate_member_count'] : null;
                                // Use invite API online count as fallback if widget was disabled
                                if ($onlineCount === null && isset($d['approximate_presence_count'])) {
                                    $onlineCount = (int) $d['approximate_presence_count'];
                                }
                                if (!$inviteUrl) {
                                    $inviteUrl   = 'https://discord.gg/' . $inviteCode;
                                    $displayAddr = 'discord.gg/' . $inviteCode;
                                }
                            }
                        }
                    }

                    return [
                        'channels'    => $channels,
                        'slotsUsed'   => $onlineCount,
                        'slotsMax'    => $memberCount,
                        'displayAddr' => $displayAddr,
                        'inviteUrl'   => $inviteUrl,
                    ];
                });
            }
        } catch (\Throwable) {
            // silent fail — show "—"
        }

        return $default;
    }
}

