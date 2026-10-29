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

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $game    = $request->query('game', '');
        $sort    = $request->query('sort', 'members');
        $allowed = ['members', 'avg_skill', 'kills', 'deaths', 'connection_time'];
        $sort    = in_array($sort, $allowed) ? $sort : 'members';

        $countries = DB::table('hlstats_Players')
            ->select(
                'country',
                DB::raw('MAX(flag) as flagCode'),
                DB::raw('COUNT(*) as members'),
                DB::raw('ROUND(AVG(skill), 0) as avg_skill'),
                DB::raw('ROUND(AVG(activity), 0) as avg_activity'),
                DB::raw('SUM(connection_time) as connection_time'),
                DB::raw('SUM(kills) as kills'),
                DB::raw('SUM(deaths) as deaths'),
                DB::raw('IFNULL(ROUND(SUM(kills)/IF(SUM(deaths)=0,1,SUM(deaths)),2),0) as kd')
            )
            ->where('hideranking', 0)
            ->when($game, fn($q) => $q->where('game', $game))
            ->groupBy('country')
            ->orderByDesc($sort)
            ->paginate(50);

        $maxSkill = $countries->max('avg_skill') ?: 1;

        return view('frontend.countries.index', compact('countries', 'game', 'sort', 'maxSkill'));
    }

    public function clans(Request $request)
    {
        $game = $request->query('game', '');

        // Aggregate clans by the flag of their members (majority flag)
        $countryClanRows = DB::select("
            SELECT
                p.flag,
                p.country,
                COUNT(DISTINCT c.clanId) AS clan_count,
                COUNT(DISTINCT p.playerId) AS player_count,
                SUM(p.kills) AS kills,
                SUM(p.deaths) AS deaths
            FROM hlstats_Clans c
            INNER JOIN hlstats_Players p ON p.clan = c.clanId AND p.hideranking = 0
            " . ($game ? "AND p.game = ?" : "") . "
            " . ($game ? "WHERE c.game = ?" : "") . "
            GROUP BY p.flag, p.country
            ORDER BY clan_count DESC, player_count DESC
        ", $game ? [$game, $game] : []);

        return view('frontend.countries.clans', compact('countryClanRows', 'game'));
    }

    public function clanDetail(Request $request, string $flag)
    {
        $game = $request->query('game', '');

        // Country info
        $country = DB::table('hlstats_Players')
            ->where('flag', $flag)
            ->when($game, fn($q) => $q->where('game', $game))
            ->select('country', 'flag')
            ->first();
        abort_if(!$country, 404);

        // Clans that have at least one member from this country
        $clans = DB::table('hlstats_Clans as c')
            ->join('hlstats_Players as p', function ($j) use ($flag, $game) {
                $j->on('p.clan', '=', 'c.clanId')
                  ->where('p.flag', $flag)
                  ->where('p.hideranking', 0);
                if ($game) $j->where('p.game', $game);
            })
            ->when($game, fn($q) => $q->where('c.game', $game))
            ->select(
                'c.clanId', 'c.name', 'c.tag',
                DB::raw('COUNT(DISTINCT p.playerId) AS member_count'),
                DB::raw('SUM(p.kills) AS kills'),
                DB::raw('SUM(p.deaths) AS deaths')
            )
            ->groupBy('c.clanId', 'c.name', 'c.tag')
            ->orderByDesc('member_count')
            ->paginate(50);

        return view('frontend.countries.clan-detail', compact('country', 'clans', 'game', 'flag'));
    }
}
