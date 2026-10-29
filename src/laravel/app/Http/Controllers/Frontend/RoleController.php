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

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $game     = $request->query('game', '');
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        $roles = DB::table('hlstats_Roles')
            ->when($game, fn($q) => $q->where('game', $game))
            ->orderBy('name')
            ->get();

        return view('frontend.roles.index', compact('roles', 'game', 'realgame'));
    }

    public function show(Request $request, string $code)
    {
        $game     = $request->query('game', '');
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        $role = DB::table('hlstats_Roles')
            ->where('code', $code)
            ->when($game, fn($q) => $q->where('game', $game))
            ->first();
        abort_if(!$role, 404);

        // Players who have played this role the most (via Events_PlayerRoles if exists, otherwise show stats)
        $players = DB::table('hlstats_Players as p')
            ->where('p.game', $game ?: $role->game)
            ->where('p.hideranking', 0)
            ->orderByDesc('p.skill')
            ->limit(50)
            ->get(['p.playerId', 'p.lastName', 'p.flag', 'p.kills', 'p.skill']);

        return view('frontend.roles.show', compact('role', 'players', 'game', 'realgame'));
    }
}
