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

class IngameController extends Controller
{
    private function resolveGame(Request $request): string
    {
        return $request->query('game', '');
    }

    public function players(Request $request)
    {
        $game = $this->resolveGame($request);
        $players = DB::table('hlstats_Players')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('hideranking', 0)
            ->orderByDesc('skill')
            ->limit(20)
            ->get(['playerId', 'lastName', 'flag', 'kills', 'deaths', 'skill']);
        return view('frontend.ingame.players', compact('players', 'game'));
    }

    public function clans(Request $request)
    {
        $game = $this->resolveGame($request);
        $clans = DB::table('hlstats_Clans as c')
            ->join('hlstats_Players as p', 'p.clan', '=', 'c.clanId')
            ->when($game, fn($q) => $q->where('c.game', $game)->where('p.game', $game))
            ->where('p.hideranking', 0)
            ->groupBy('c.clanId', 'c.name', 'c.tag')
            ->select('c.clanId', 'c.name', 'c.tag',
                DB::raw('COUNT(DISTINCT p.playerId) AS members'),
                DB::raw('SUM(p.kills) AS kills'),
                DB::raw('SUM(p.deaths) AS deaths'))
            ->orderByDesc('kills')
            ->limit(20)
            ->get();
        return view('frontend.ingame.clans', compact('clans', 'game'));
    }

    public function maps(Request $request)
    {
        $game = $this->resolveGame($request);
        $maps = DB::table('hlstats_Events_Frags as ef')
            ->join('hlstats_Players as p', 'p.playerId', '=', 'ef.killerId')
            ->when($game, fn($q) => $q->where('p.game', $game))
            ->where('p.hideranking', 0)
            ->groupBy('ef.map')
            ->select('ef.map', DB::raw('COUNT(*) AS kills'))
            ->orderByDesc('kills')
            ->limit(20)
            ->get();
        return view('frontend.ingame.maps', compact('maps', 'game'));
    }

    public function servers(Request $request)
    {
        $game = $this->resolveGame($request);
        $servers = DB::table('hlstats_Servers')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('isPublic', 1)
            ->get(['serverId', 'name', 'address', 'port', 'game', 'act_map', 'act_players', 'max_players']);
        return view('frontend.ingame.servers', compact('servers', 'game'));
    }

    public function weapons(Request $request)
    {
        $game = $this->resolveGame($request);
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;
        $weapons = DB::table('hlstats_Weapons')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('kills', '>', 0)
            ->orderByDesc('kills')
            ->limit(20)
            ->get(['weaponId', 'name', 'kills', 'headshots']);
        return view('frontend.ingame.weapons', compact('weapons', 'game', 'realgame'));
    }

    public function statsme(Request $request)
    {
        $game     = $this->resolveGame($request);
        $steamId  = $request->query('steamid', '');
        $playerId = $request->query('pid', 0);

        $player = null;
        if ($playerId) {
            $player = DB::table('hlstats_Players')->where('playerId', $playerId)->first();
        } elseif ($steamId) {
            $player = DB::table('hlstats_Players')
                ->join('hlstats_PlayerUniqueIds as uid', 'uid.playerId', '=', 'hlstats_Players.playerId')
                ->where('uid.uniqueId', $steamId)
                ->when($game, fn($q) => $q->where('hlstats_Players.game', $game))
                ->select('hlstats_Players.*')
                ->first();
        }
        return view('frontend.ingame.statsme', compact('player', 'game'));
    }

    public function motd(Request $request)
    {
        $game = $this->resolveGame($request);
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        $topPlayers = DB::table('hlstats_Players')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('hideranking', 0)
            ->orderByDesc('skill')
            ->limit(10)
            ->get(['playerId', 'lastName', 'flag', 'kills', 'skill']);

        return view('frontend.ingame.motd', compact('topPlayers', 'game', 'realgame'));
    }
}
