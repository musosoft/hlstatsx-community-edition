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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VoiceCommController extends Controller
{
    // serverType constants matching the DB
    const TYPE_TS      = 0;
    const TYPE_STEAM   = 1;
    const TYPE_DISCORD = 2;

    public function index()
    {
        $servers = DB::table('hlstats_Servers_VoiceComm')
            ->orderBy('serverType')
            ->orderBy('name')
            ->get();

        $tsServers      = $servers->where('serverType', self::TYPE_TS)->values();
        $steamServers   = $servers->where('serverType', self::TYPE_STEAM)->values();
        $discordServers = $servers->where('serverType', self::TYPE_DISCORD)->values();

        return view('frontend.voicecomm.index', compact('tsServers', 'steamServers', 'discordServers'));
    }

    public function steam(int $id)
    {
        $server = DB::table('hlstats_Servers_VoiceComm')
            ->where('serverId', $id)
            ->where('serverType', self::TYPE_STEAM)
            ->first();

        abort_if(!$server, 404);

        $groupUrl = $server->addr;
        $group    = null;
        $error    = null;

        try {
            $cacheKey = 'steam_group_' . md5($groupUrl);
            $xml = Cache::remember($cacheKey, 300, function () use ($groupUrl) {
                $isNumeric = ctype_digit((string) $groupUrl);
                $apiUrl = $isNumeric
                    ? 'https://steamcommunity.com/gid/' . $groupUrl . '/memberslistxml/?xml=1'
                    : 'https://steamcommunity.com/groups/' . rawurlencode($groupUrl) . '/memberslistxml/?xml=1';
                $response = Http::timeout(5)->get($apiUrl);
                return $response->successful() ? $response->body() : null;
            });

            if ($xml) {
                $parsed = @simplexml_load_string($xml);
                if ($parsed && isset($parsed->groupDetails)) {
                    $d = $parsed->groupDetails;
                    $group = [
                        'name'          => (string) ($d->groupName ?? $server->name),
                        'url'           => (string) ($d->groupURL ?? ''),
                        'headline'      => (string) ($d->headline ?? ''),
                        'summary'       => (string) ($d->summary ?? ''),
                        'avatar'        => (string) ($d->avatarFull ?? $d->avatarMedium ?? ''),
                        'memberCount'   => (int)    ($d->memberCount ?? 0),
                        'membersInChat' => (int)    ($d->membersInChat ?? 0),
                        'membersInGame' => (int)    ($d->membersInGame ?? 0),
                        'membersOnline' => (int)    ($d->membersOnline ?? 0),
                        'groupId64'     => (string) ($parsed->groupID64 ?? ''),
                    ];
                } else {
                    $error = 'Could not parse Steam group data. The group URL may be incorrect or the group is private.';
                }
            } else {
                $error = 'Could not fetch Steam group data.';
            }
        } catch (\Throwable $e) {
            $error = 'Could not fetch Steam group data.';
        }

        return view('frontend.voicecomm.steam', compact('server', 'group', 'error'));
    }

    public function discord(int $id)
    {
        $server = DB::table('hlstats_Servers_VoiceComm')
            ->where('serverId', $id)
            ->where('serverType', self::TYPE_DISCORD)
            ->first();

        abort_if(!$server, 404);

        $guildId = $server->addr;
        $widget  = null;
        $error   = null;

        try {
            $cacheKey = 'discord_widget_' . md5($guildId);
            $widget = Cache::remember($cacheKey, 60, function () use ($guildId) {
                $response = Http::timeout(5)->get(
                    'https://discord.com/api/guilds/' . rawurlencode($guildId) . '/widget.json'
                );
                if ($response->successful()) {
                    return $response->json();
                }
                return null;
            });
        } catch (\Throwable $e) {
            $error = 'Could not fetch Discord widget data.';
        }

        if ($widget && isset($widget['code'])) {
            // code=50004 means widget is disabled
            $error  = 'The Discord server widget is disabled. Ask the server owner to enable it in Server Settings → Widget.';
            $widget = null;
        }

        return view('frontend.voicecomm.discord', compact('server', 'widget', 'error'));
    }

    public function teamspeak(int $id)
    {
        $server = DB::table('hlstats_Servers_VoiceComm')
            ->where('serverId', $id)
            ->where('serverType', self::TYPE_TS)
            ->first();

        abort_if(!$server, 404);

        $data  = null;
        $error = null;

        try {
            $data = $this->fetchTeamSpeakData(
                $server->addr,
                (int) $server->queryPort,
                (int) $server->UDPPort,
                $server->password ?? ''
            );
        } catch (\Throwable $e) {
            $error = 'Could not connect to TeamSpeak 3 server: ' . $e->getMessage();
        }

        return view('frontend.voicecomm.teamspeak', compact('server', 'data', 'error'));
    }

    private function fetchTeamSpeakData(string $host, int $queryPort, int $udpPort, string $password): array
    {
        $sock = @fsockopen($host, $queryPort, $errno, $errstr, 5);
        if (!$sock) {
            throw new \RuntimeException("Connection refused ({$errno}: {$errstr})");
        }
        stream_set_timeout($sock, 5);

        $this->tsRead($sock); // Welcome banner
        $this->tsRead($sock); // Second line

        // Login with anonymous query (no password needed for read-only on most servers)
        $this->tsSend($sock, "use port={$udpPort}");
        $this->tsRead($sock);

        // Get server info
        $this->tsSend($sock, 'serverinfo');
        $srvInfo = $this->tsParseKV($this->tsRead($sock));

        // Get channel list
        $this->tsSend($sock, 'channellist -topic -limits');
        $channels = $this->tsParseList($this->tsRead($sock));

        // Get client list
        $this->tsSend($sock, 'clientlist -away -voice -info');
        $clients = array_filter(
            $this->tsParseList($this->tsRead($sock)),
            fn($c) => ($c['client_type'] ?? 0) == 0  // 0=normal, 1=query
        );

        $this->tsSend($sock, 'quit');
        fclose($sock);

        // Build channel tree keyed by channel id
        $channelMap = [];
        foreach ($channels as $ch) {
            $channelMap[(int)($ch['cid'] ?? 0)] = $ch;
        }

        // Group clients by channel
        $clientsByChannel = [];
        foreach ($clients as $cl) {
            $cid = (int)($cl['cid'] ?? 0);
            $clientsByChannel[$cid][] = $cl;
        }

        return [
            'info'            => $srvInfo,
            'channels'        => $channels,
            'channelMap'      => $channelMap,
            'clients'         => array_values($clients),
            'clientsByChannel' => $clientsByChannel,
        ];
    }

    private function tsRead($sock): string
    {
        $line = '';
        while (!feof($sock)) {
            $char = fgetc($sock);
            if ($char === false || $char === "\n") break;
            $line .= $char;
        }
        return rtrim($line, "\r\n");
    }

    private function tsSend($sock, string $cmd): void
    {
        fwrite($sock, $cmd . "\n");
    }

    private function tsUnescape(string $s): string
    {
        return str_replace(
            ['\\\\', '\\/', '\\s', '\\p', '\\a', '\\b', '\\f', '\\n', '\\r', '\\t', '\\v'],
            ['\\',   '/',   ' ',  '|',  "\x07", "\x08", "\x0C", "\n",  "\r",  "\t",  "\x0B"],
            $s
        );
    }

    private function tsParseKV(string $line): array
    {
        $result = [];
        // Grab first data line (before 'error id=0')
        $parts = explode('error', $line);
        $data  = trim($parts[0]);
        foreach (explode(' ', $data) as $pair) {
            if (str_contains($pair, '=')) {
                [$k, $v] = explode('=', $pair, 2);
                $result[$k] = $this->tsUnescape($v);
            }
        }
        return $result;
    }

    private function tsParseList(string $line): array
    {
        $result = [];
        $parts  = explode('error', $line);
        $data   = trim($parts[0]);
        foreach (explode('|', $data) as $entry) {
            $item = [];
            foreach (explode(' ', trim($entry)) as $pair) {
                if (str_contains($pair, '=')) {
                    [$k, $v] = explode('=', $pair, 2);
                    $item[$k] = $this->tsUnescape($v);
                }
            }
            if (!empty($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }
}
