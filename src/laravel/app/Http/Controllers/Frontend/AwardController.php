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
use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AwardController extends Controller
{
    public function index(Request $request)
    {
        $game = $request->query('game', '');
        $tab  = $request->query('tab', 'daily');

        // Resolve the real game used for image assets
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        // Daily awards
        $dailyAwards = Award::with(['dailyWinner'])
            ->when($game, fn($q) => $q->where('game', $game))
            ->orderBy('name')
            ->get();

        // Global awards
        $globalAwards = Award::with(['globalWinner'])
            ->when($game, fn($q) => $q->where('game', $game))
            ->orderBy('name')
            ->get();

        // Ranks with player count
        $rankCounts = [];
        if ($game) {
            $counts = DB::select("
                SELECT r.rankId, COUNT(p.playerId) AS obj_count
                FROM hlstats_Ranks r
                INNER JOIN hlstats_Players p ON p.game = r.game
                WHERE r.game = ? AND p.kills >= r.minKills AND p.kills <= r.maxKills
                GROUP BY r.rankId
            ", [$game]);
            foreach ($counts as $c) {
                $rankCounts[$c->rankId] = $c->obj_count;
            }
        }
        $ranks = DB::table('hlstats_Ranks')
            ->when($game, fn($q) => $q->where('game', $game))
            ->orderBy('minKills')
            ->get();

        // Ribbons grouped by awardCount
        $ribbons = DB::select("
            SELECT
                r.ribbonId, r.ribbonName, r.image, r.awardCount,
                a.name AS awardName,
                COUNT(pr.ribbonId) AS achievedcount
            FROM hlstats_Ribbons r
            INNER JOIN hlstats_Awards a ON a.code = r.awardCode AND a.game = r.game
            LEFT JOIN hlstats_Players_Ribbons pr ON pr.ribbonId = r.ribbonId
            WHERE r.game = ? AND r.special = 0
            GROUP BY r.ribbonId, r.ribbonName, r.image, r.awardCount, a.name
            ORDER BY r.awardCount, r.ribbonName
        ", [$game ?: '']);

        return view('frontend.awards.index', compact(
            'dailyAwards', 'globalAwards', 'ranks', 'rankCounts', 'ribbons', 'game', 'realgame', 'tab'
        ));
    }

    public function awardDetail(int $id, Request $request)
    {
        $game     = $request->query('game', '');
        $award    = Award::findOrFail($id);
        $realgame = $game
            ? (DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game)
            : $game;

        $history = DB::table('hlstats_Players_Awards as pa')
            ->join('hlstats_Players as p', 'p.playerId', '=', 'pa.playerId')
            ->where('pa.awardId', $id)
            ->orderByDesc('pa.awardTime')
            ->paginate(30, ['pa.playerId', 'pa.awardTime', 'pa.count', 'p.lastName', 'p.flag']);

        return view('frontend.awards.detail', compact('award', 'history', 'game', 'realgame'));
    }

    public function rankDetail(int $id, Request $request)
    {
        $game = $request->query('game', '');
        $rank = DB::table('hlstats_Ranks')->where('rankId', $id)->first();
        abort_if(!$rank, 404);

        $players = DB::table('hlstats_Players as p')
            ->where('p.game', $game ?: $rank->game)
            ->whereBetween('p.kills', [$rank->minKills, $rank->maxKills])
            ->where('p.hideranking', 0)
            ->orderByDesc('p.skill')
            ->paginate(50, ['p.playerId', 'p.lastName', 'p.flag', 'p.kills', 'p.skill']);

        return view('frontend.awards.rank-detail', compact('rank', 'players', 'game'));
    }

    public function ribbonDetail(int $id, Request $request)
    {
        $game   = $request->query('game', '');
        $ribbon = DB::table('hlstats_Ribbons as r')
            ->leftJoin('hlstats_Awards as a', function ($j) {
                $j->on('a.code', '=', 'r.awardCode')->whereColumn('a.game', 'r.game');
            })
            ->where('r.ribbonId', $id)
            ->select('r.*', 'a.name AS awardName')
            ->first();
        abort_if(!$ribbon, 404);

        $realgame = DB::table('hlstats_Games')->where('code', $ribbon->game)->value('realgame') ?? $ribbon->game;

        $players = DB::table('hlstats_Players_Ribbons as pr')
            ->join('hlstats_Players as p', 'p.playerId', '=', 'pr.playerId')
            ->where('pr.ribbonId', $id)
            ->where('p.hideranking', 0)
            ->orderByDesc('p.skill')
            ->paginate(50, ['p.playerId', 'p.lastName', 'p.flag', 'p.kills', 'p.skill']);

        return view('frontend.awards.ribbon-detail', compact('ribbon', 'players', 'game', 'realgame'));
    }
}
