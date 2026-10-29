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

class ActionController extends Controller
{
    public function index(Request $request)
    {
        $game = $request->query('game', '');

        $total = DB::table('hlstats_Actions')
            ->when($game, fn($q) => $q->where('game', $game))
            ->sum('count');

        $actions = DB::table('hlstats_Actions')
            ->select('id', 'description', 'count', 'reward_player')
            ->when($game, fn($q) => $q->where('game', $game))
            ->where('count', '>', 0)
            ->orderByDesc('count')
            ->paginate(50);

        return view('frontend.actions.index', compact('actions', 'game', 'total'));
    }

    public function show(Request $request, int $id)
    {
        $game   = $request->query('game', '');
        $action = DB::table('hlstats_Actions')->where('id', $id)->firstOrFail();

        $total = DB::table('hlstats_Events_PlayerActions as ea')
            ->join('hlstats_Players as p', 'ea.playerId', '=', 'p.playerId')
            ->where('ea.actionId', $id)
            ->where('p.hideranking', 0)
            ->when($game, fn($q) => $q->where('p.game', $game))
            ->count();

        $players = DB::table('hlstats_Events_PlayerActions as ea')
            ->join('hlstats_Players as p', 'ea.playerId', '=', 'p.playerId')
            ->select(
                'p.playerId',
                'p.lastName',
                'p.country',
                'p.flag',
                DB::raw('COUNT(ea.id) AS achieved'),
                DB::raw('COUNT(ea.id) * ' . (int) $action->reward_player . ' AS skill_bonus')
            )
            ->where('ea.actionId', $id)
            ->where('p.hideranking', 0)
            ->when($game, fn($q) => $q->where('p.game', $game))
            ->groupBy('p.playerId', 'p.lastName', 'p.country', 'p.flag')
            ->orderByDesc('achieved')
            ->paginate(40);

        return view('frontend.actions.show', compact('action', 'players', 'game', 'total'));
    }
}
