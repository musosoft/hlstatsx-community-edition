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

class WeaponController extends Controller
{
    public function index(Request $request)
    {
        $game = $request->query('game', '');

        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        $totals = DB::table('hlstats_Weapons')
            ->when($game, fn($q) => $q->where('game', $game))
            ->selectRaw('IF(IFNULL(SUM(kills),0)=0,1,SUM(kills)) as total_kills, IF(IFNULL(SUM(headshots),0)=0,1,SUM(headshots)) as total_headshots')
            ->first();

        $totalKills      = (int) ($totals->total_kills      ?? 1);
        $totalHeadshots  = (int) ($totals->total_headshots  ?? 1);

        $weapons = DB::table('hlstats_Weapons')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('kills', '>', 0)
            ->selectRaw("
                weaponId, code, name, modifier, kills, headshots,
                ROUND(kills / ? * 100, 2)              AS kpercent,
                ROUND(headshots / IF(kills=0,1,kills), 2) AS hpk,
                ROUND(headshots / ? * 100, 2)          AS hpercent
            ", [$totalKills, $totalHeadshots])
            ->orderByDesc('kills')
            ->paginate(50);

        return view('frontend.weapons.index', compact('weapons', 'game', 'realgame', 'totalKills', 'totalHeadshots'));
    }

    public function show(Request $request, string $code)
    {
        $game   = $request->query('game', '');
        $weapon = DB::table('hlstats_Weapons')
            ->where('code', $code)
            ->when($game, fn($q) => $q->where('game', $game))
            ->first();

        if (!$weapon) {
            abort(404);
        }

        $realgame = DB::table('hlstats_Games')->where('code', $weapon->game ?? $game)->value('realgame') ?? ($weapon->game ?? $game);
        $totals = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Servers as s', 'f.serverId', '=', 's.serverId')
            ->where('f.weapon', $code)
            ->when($game, fn($q) => $q->where('s.game', $game))
            ->selectRaw('COUNT(DISTINCT f.killerId) as unique_players, COUNT(*) as total_kills, SUM(f.headshot=1) as total_headshots')
            ->first();

        $players = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Players as p', 'f.killerId', '=', 'p.playerId')
            ->where('f.weapon', $code)
            ->where('p.hideranking', 0)
            ->when($game, fn($q) => $q->where('p.game', $game))
            ->select(
                'p.playerId',
                'p.lastName',
                'p.country',
                'p.flag',
                DB::raw('COUNT(f.id) AS frags'),
                DB::raw('SUM(f.headshot=1) AS headshots'),
                DB::raw('ROUND(SUM(f.headshot=1) / COUNT(f.id), 4) AS hpk')
            )
            ->groupBy('f.killerId', 'p.playerId', 'p.lastName', 'p.country', 'p.flag')
            ->orderByDesc('frags')
            ->paginate(50);

        return view('frontend.weapons.show', compact('weapon', 'players', 'game', 'realgame', 'totals'));
    }
}
