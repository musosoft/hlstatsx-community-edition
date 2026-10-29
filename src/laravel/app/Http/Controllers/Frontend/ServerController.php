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
use App\Models\Server;
use App\Services\StatsService;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function __construct(private StatsService $stats) {}

    public function index(Request $request)
    {
        $game    = $request->query('game', '');
        $servers = Server::visible()
            ->when($game, fn($q) => $q->forGame($game))
            ->with('game')
            ->get();

        return view('frontend.servers.index', compact('servers', 'game'));
    }

    public function show(int $id)
    {
        $server        = Server::with('game')->findOrFail($id);
        $onlinePlayers = $this->stats->getServerPlayers($id);
        $chart         = $this->stats->getActivityChart($id);
        $chartLabels   = $chart['labels'] ?? [];
        $chartData     = $chart['kills']  ?? [];

        return view('frontend.servers.show', compact('server', 'onlinePlayers', 'chartLabels', 'chartData'));
    }

    public function status(int $id)
    {
        $server = Server::findOrFail($id);
        return response()->json([
            'online'      => $server->last_event >= now()->subMinutes(5)->timestamp,
            'act_players' => $server->act_players,
            'max_players' => $server->max_players,
            'act_map'     => $server->act_map,
        ]);
    }
}
