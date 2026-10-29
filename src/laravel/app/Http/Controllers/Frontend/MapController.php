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
use App\Models\GameMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $game = $request->query('game', '');

        $totals = DB::table('hlstats_Maps_Counts')
            ->when($game, fn($q) => $q->where('game', $game))
            ->selectRaw('IF(IFNULL(SUM(kills),0)=0,1,SUM(kills)) as total_kills, IF(IFNULL(SUM(headshots),0)=0,1,SUM(headshots)) as total_headshots')
            ->first();

        $totalKills     = (int) ($totals->total_kills     ?? 1);
        $totalHeadshots = (int) ($totals->total_headshots ?? 1);

        $maps = DB::table('hlstats_Maps_Counts')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('kills', '>', 0)
            ->selectRaw("
                rowId,
                IF(map = '', '(Unaccounted)', map) AS map,
                kills, headshots,
                ROUND(kills / ? * 100, 2)                        AS kpercent,
                ROUND(headshots / IF(kills=0,1,kills), 2)        AS hpk,
                ROUND(headshots / ? * 100, 2)                    AS hpercent
            ", [$totalKills, $totalHeadshots])
            ->orderByDesc('kills')
            ->paginate(50);

        return view('frontend.maps.index', compact('maps', 'game', 'totalKills', 'totalHeadshots'));
    }

    public function show(Request $request, string $map)
    {
        $game = $request->query('game', '');

        $totals = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Servers as s', 'f.serverId', '=', 's.serverId')
            ->where('f.map', $map)
            ->when($game, fn($q) => $q->where('s.game', $game))
            ->selectRaw('COUNT(DISTINCT f.killerId) as unique_players, COUNT(*) as total_kills')
            ->first();

        $players = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Players as p', 'f.killerId', '=', 'p.playerId')
            ->where('f.map', $map)
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

        return view('frontend.maps.show', compact('map', 'players', 'game', 'totals'));
    }

    public function markers()
    {
        // Returns GeoJSON markers for API use
        $servers = \App\Models\Server::visible()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get(['serverId', 'name', 'address', 'port', 'last_event', 'lat', 'lng']);

        return response()->json($servers->map(fn($s) => [
            'lat'     => $s->lat,
            'lng'     => $s->lng,
            'name'    => $s->name,
            'address' => $s->full_address,
            'online'  => $s->last_event >= now()->subMinutes(5)->timestamp,
        ]));
    }
}
