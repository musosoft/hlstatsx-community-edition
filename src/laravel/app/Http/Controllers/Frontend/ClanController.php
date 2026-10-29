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
use App\Models\Clan;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClanController extends Controller
{
    public function index(Request $request)
    {
        $game  = $request->query('game', '');
        $sort  = $request->query('sort', 'avg_skill');
        $minMembers = filter_var($request->query('minmembers', 3), FILTER_VALIDATE_INT);
        if ($minMembers === false || $minMembers < 1 || $minMembers > 99) {
            $minMembers = 3;
        }

        $allowedSorts = ['avg_skill', 'kills', 'deaths', 'headshots', 'members_count', 'total_connection_time', 'name', 'tag'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'avg_skill';

        $clans = Clan::visible()
            ->when($game, fn($q) => $q->forGame($game))
            ->whereHas('players', fn($q) => $q->where('hideranking', 0), '>=', $minMembers)
            ->addSelect([
                'kills'                 => Player::selectRaw('COALESCE(SUM(kills), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'deaths'               => Player::selectRaw('COALESCE(SUM(deaths), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'headshots'            => Player::selectRaw('COALESCE(SUM(headshots), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'members_count'        => Player::selectRaw('COUNT(*)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'avg_skill'            => Player::selectRaw('COALESCE(ROUND(AVG(skill)), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'avg_activity'         => Player::selectRaw('COALESCE(TRUNCATE(AVG(activity), 2), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
                'total_connection_time' => Player::selectRaw('COALESCE(SUM(connection_time), 0)')
                                                 ->whereColumn('clan', 'hlstats_Clans.clanId')
                                                 ->where('hideranking', 0),
            ])
            ->orderByDesc($sort)
            ->paginate(50);

        return view('frontend.clans.index', compact('clans', 'game', 'sort', 'minMembers'));
    }

    public function show(int $id)
    {
        $clan     = Clan::findOrFail($id);
        $game     = $clan->game;
        $realgame = DB::table('hlstats_Games')->where('code', $game)->value('realgame') ?? $game;

        $members = $clan->players()
            ->ranked()
            ->orderByDesc('skill')
            ->get();

        // --- Weapons tab ---
        $totalKills = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Players as p', 'f.killerId', '=', 'p.playerId')
            ->where('p.clan', $id)
            ->count();
        $totalKills = max(1, $totalKills);

        $weapons = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Players as p', 'f.killerId', '=', 'p.playerId')
            ->leftJoin('hlstats_Weapons as w', function ($j) use ($game) {
                $j->on('w.code', '=', 'f.weapon')->where('w.game', $game);
            })
            ->where('p.clan', $id)
            ->groupBy('f.weapon', 'w.name', 'w.modifier')
            ->selectRaw('f.weapon, IFNULL(w.name, f.weapon) AS weapon_name,
                IFNULL(w.modifier, 1.00) AS modifier,
                COUNT(*) AS kills,
                ROUND(COUNT(*) / ? * 100, 2) AS kpercent,
                SUM(f.headshot = 1) AS headshots,
                IF(COUNT(*) > 0, ROUND(SUM(f.headshot=1)/COUNT(*), 4), 0) AS hpk', [$totalKills])
            ->orderByDesc('kills')
            ->get();

        // --- Maps tab — single query with conditional aggregation ---
        $maps = DB::table('hlstats_Events_Frags as f')
            ->leftJoin('hlstats_Players as kp', 'f.killerId', '=', 'kp.playerId')
            ->leftJoin('hlstats_Players as vp', 'f.victimId', '=', 'vp.playerId')
            ->where(fn($q) => $q->where('kp.clan', $id)->orWhere('vp.clan', $id))
            ->groupBy('f.map')
            ->selectRaw("
                IF(f.map='','(Unaccounted)',f.map)                                     AS map,
                SUM(kp.clan = ?)                                                       AS kills,
                SUM(vp.clan = ?)                                                       AS deaths,
                SUM(kp.clan = ? AND f.headshot = 1)                                    AS headshots,
                ROUND(SUM(kp.clan = ?) / ? * 100, 2)                                  AS kpercent,
                IF(SUM(kp.clan = ?) > 0,
                    ROUND(SUM(kp.clan = ? AND f.headshot = 1) / SUM(kp.clan = ?), 4),
                    0)                                                                 AS hpk
            ", [$id, $id, $id, $id, $totalKills, $id, $id, $id])
            ->orderByDesc('kills')
            ->get()
            ->map(function ($row) {
                $row->kd = $row->deaths > 0 ? round($row->kills / $row->deaths, 2) : $row->kills;
                return $row;
            });

        // --- Teams tab ---
        $totalTeamJoins = DB::table('hlstats_Events_ChangeTeam as ct')
            ->join('hlstats_Players as p', 'ct.playerId', '=', 'p.playerId')
            ->where('p.clan', $id)
            ->count();
        $totalTeamJoins = max(1, $totalTeamJoins);

        $teams = DB::table('hlstats_Events_ChangeTeam as ct')
            ->join('hlstats_Players as p', 'ct.playerId', '=', 'p.playerId')
            ->where('p.clan', $id)
            ->groupBy('ct.team')
            ->selectRaw('ct.team AS name, COUNT(*) AS teamcount,
                ROUND(COUNT(*)/?*100,2) AS percent', [$totalTeamJoins])
            ->orderByDesc('teamcount')
            ->get();

        // --- Actions tab ---
        $actions = DB::select("
            SELECT a.id AS action_id, a.code, a.description, SUM(e.obj_count) AS obj_count, SUM(e.obj_bonus) AS obj_bonus
            FROM hlstats_Actions a
            LEFT JOIN (
                SELECT actionId, COUNT(*) AS obj_count, SUM(bonus) AS obj_bonus, playerId
                FROM hlstats_Events_PlayerActions GROUP BY actionId, playerId
                UNION ALL
                SELECT actionId, COUNT(*) AS obj_count, SUM(bonus) AS obj_bonus, playerId
                FROM hlstats_Events_PlayerPlayerActions GROUP BY actionId, playerId
            ) e ON e.actionId = a.id
            LEFT JOIN hlstats_Players p ON p.playerId = e.playerId
            WHERE p.clan = ? OR p.clan IS NULL
            GROUP BY a.id, a.code, a.description
            HAVING obj_count > 0
            ORDER BY obj_count DESC
        ", [$id]);

        return view('frontend.clans.show', compact(
            'clan', 'game', 'realgame', 'members',
            'weapons', 'maps', 'teams', 'actions'
        ));
    }
}
