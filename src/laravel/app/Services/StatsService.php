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

namespace App\Services;

use App\Models\EventKill;
use App\Models\Game;
use App\Models\Player;
use App\Models\Server;
use App\Models\Award;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * Get site-wide global statistics.
     */
    public function getGlobalStats(): array
    {
        return Cache::remember('global_stats', 300, function () {
            $players  = Player::ranked()->count();
            $clans    = DB::table('hlstats_Clans')->where('hidden', 0)->count();
            $games    = Game::visible()->count();
            $servers  = Server::visible()->count();
            $kills    = DB::table('hlstats_Players')->sum('kills');
            $lastKill = EventKill::orderByDesc('eventTime')->value('eventTime');

            return compact('players', 'clans', 'games', 'servers', 'kills', 'lastKill');
        });
    }

    /**
     * Get paginated list of top players, optionally filtered.
     * period: 0=global, 1=yesterday, 2=last weekend, 3=last 7 days, 4=last 28 days
     */
    public function getTopPlayers(
        string $game,
        int $perPage = 50,
        array $filters = [],
        string $sort = 'skill',
        int $period = 0
    ): LengthAwarePaginator {
        $allowedSorts = ['skill', 'kills', 'deaths', 'headshots', 'connection_time', 'kd_ratio', 'hs_percent', 'accuracy'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'skill';

        // For period-filtered rankings, use raw SQL against Players_History
        if ($period > 0) {
            return $this->getTopPlayersFiltered($game, $perPage, $filters, $sort, $period);
        }

        $query = Player::with(['clanRelation', 'uniqueIds'])
            ->forGame($game)
            ->ranked();

        // kd_ratio, hs_percent, accuracy are not real columns — compute them
        $computedSorts = ['kd_ratio', 'hs_percent', 'accuracy'];
        if (in_array($sort, $computedSorts)) {
            $query->selectRaw('hlstats_Players.*,
                ROUND(IF(deaths = 0, kills, kills / deaths), 2)          AS kd_ratio,
                ROUND(IF(kills  = 0, 0, headshots / kills * 100), 2)     AS hs_percent,
                ROUND(IF(shots  = 0, 0, hits / shots * 100), 1)          AS accuracy'
            );
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('lastName', 'like', '%' . $search . '%');
        }

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        return $query->orderByDesc($sort)->paginate($perPage)->withQueryString();
    }

    /**
     * Time-filtered player ranking using Players_History table.
     */
    private function getTopPlayersFiltered(
        string $game,
        int $perPage,
        array $filters,
        string $sort,
        int $period
    ): ManualPaginator {
        $dateFilter = match ($period) {
            1 => "a.eventTime >= CURDATE() - INTERVAL 1 DAY AND a.eventTime < CURDATE()",
            2 => "YEARWEEK(a.eventTime, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1) AND WEEKDAY(a.eventTime) IN (5,6)",
            3 => "a.eventTime >= NOW() - INTERVAL 7 DAY",
            4 => "a.eventTime >= NOW() - INTERVAL 28 DAY",
            default => "1=1",
        };

        // Map sort aliases used in view to SQL column names
        $sqlSort = match ($sort) {
            'kd_ratio'  => 'kd_ratio',
            'hs_percent' => 'hs_percent',
            'accuracy'  => 'accuracy',
            default     => $sort,
        };

        $gameParam  = $game;
        $searchParam = $filters['search'] ?? null;
        $countryParam = $filters['country'] ?? null;

        $searchWhere  = $searchParam  ? "AND p.lastName LIKE ?" : '';
        $countryWhere = $countryParam ? "AND p.country = ?"     : '';

        $bindings = [$game];
        if ($searchParam)  $bindings[] = '%' . $searchParam . '%';
        if ($countryParam) $bindings[] = $countryParam;

        $innerSql = "
            SELECT
                a.playerId,
                p.lastName,
                p.flag,
                p.country,
                p.activity,
                SUM(a.connection_time) AS connection_time,
                SUM(a.kills)           AS kills,
                SUM(a.deaths)          AS deaths,
                SUM(a.skill_change)    AS skill,
                SUM(a.shots)           AS shots,
                SUM(a.hits)            AS hits,
                SUM(a.headshots)       AS headshots,
                ROUND(IF(SUM(a.deaths)=0, SUM(a.kills), SUM(a.kills)/SUM(a.deaths)), 2) AS kd_ratio,
                ROUND(IF(SUM(a.kills)=0, 0, SUM(a.headshots)/SUM(a.kills)*100), 2)      AS hs_percent,
                ROUND(IF(SUM(a.shots)=0, 0, SUM(a.hits)/SUM(a.shots)*100), 1)           AS accuracy
            FROM hlstats_Players_History a
            JOIN hlstats_Players p ON p.playerId = a.playerId
            WHERE {$dateFilter}
            AND a.game = ?
            AND p.hideranking = 0
            AND p.lastAddress <> ''
            {$searchWhere}
            {$countryWhere}
            GROUP BY a.playerId, p.lastName, p.flag, p.country, p.activity
        ";

        // Bindings: date-filter params first (none needed), then game, optional search/country
        $innerBindings = array_merge([$gameParam], array_slice($bindings, 1));

        $countResult = DB::select(
            "SELECT COUNT(*) AS total FROM ({$innerSql}) AS sub",
            $innerBindings
        );
        $total = (int) ($countResult[0]->total ?? 0);

        $page    = (int) request()->input('page', 1);
        $offset  = ($page - 1) * $perPage;

        $rows = DB::select(
            "{$innerSql} ORDER BY {$sqlSort} DESC LIMIT {$perPage} OFFSET {$offset}",
            $innerBindings
        );

        $paginator = new ManualPaginator(
            collect($rows),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginator;
    }

    /**
     * Get a full player profile with stats.
     */
    public function getPlayerProfile(int $playerId): array
    {
        $player = Player::with(['clanRelation'])
            ->findOrFail($playerId);

        // Weapons ranked by kills — with modifier, kill%, headshot%, hpk
        $totalKills    = max(1, (int) $player->kills);
        $totalHeadshots = max(1, (int) $player->headshots);
        $totalDeaths   = max(1, (int) $player->deaths);

        $topWeapons = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Weapons as w', function ($join) use ($player) {
                $join->on('w.code', '=', 'f.weapon')
                     ->where('w.game', '=', $player->game);
            })
            ->where('f.killerId', $playerId)
            ->select(
                'w.name', 'w.code',
                DB::raw('IFNULL(w.modifier, 1.00) AS modifier'),
                DB::raw('COUNT(f.id) AS kills'),
                DB::raw('ROUND(COUNT(f.id) / ' . $totalKills . ' * 100, 2) AS kpercent'),
                DB::raw('SUM(f.headshot=1) AS headshots'),
                DB::raw('ROUND(SUM(f.headshot=1) / IF(COUNT(f.id)=0,1,COUNT(f.id)), 4) AS hpk'),
                DB::raw('ROUND(SUM(f.headshot=1) / ' . $totalHeadshots . ' * 100, 2) AS hpercent')
            )
            ->groupBy('w.code', 'w.name', 'w.modifier')
            ->orderByDesc('kills')
            ->get();

        // Maps ranked by kills+deaths — with kills%, deaths%, K:D, headshots, hpk
        $topMaps = DB::table('hlstats_Events_Frags as f')
            ->where(function ($q) use ($playerId) {
                $q->where('f.killerId', $playerId)->orWhere('f.victimId', $playerId);
            })
            ->select(
                DB::raw("IF(f.map='', '(Unaccounted)', f.map) AS map"),
                DB::raw('SUM(f.killerId = ' . $playerId . ') AS kills'),
                DB::raw('SUM(f.victimId = ' . $playerId . ') AS deaths'),
                DB::raw('ROUND(SUM(f.killerId = ' . $playerId . ') / ' . $totalKills . ' * 100, 2) AS kpercent'),
                DB::raw('ROUND(SUM(f.victimId = ' . $playerId . ') / ' . $totalDeaths . ' * 100, 2) AS dpercent'),
                DB::raw('IFNULL(ROUND(SUM(f.killerId = ' . $playerId . ') / IF(SUM(f.victimId = ' . $playerId . ')=0,1,SUM(f.victimId = ' . $playerId . ')),2),0) AS kpd'),
                DB::raw('SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) AS headshots'),
                DB::raw('ROUND(SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) / ' . $totalHeadshots . ' * 100, 2) AS hpercent'),
                DB::raw('IFNULL(ROUND(SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) / IF(SUM(f.killerId = ' . $playerId . ')=0,1,SUM(f.killerId = ' . $playerId . ')),2),0) AS hpk')
            )
            ->groupBy('f.map')
            ->orderByDesc('kills')
            ->get();

        $topVictims = DB::table('hlstats_Events_Frags as ek')
            ->join('hlstats_Players as p', 'ek.victimId', '=', 'p.playerId')
            ->select(
                'p.playerId', 'p.lastName', 'p.country', 'p.flag',
                DB::raw('SUM(ek.killerId = ' . $playerId . ') AS kills'),
                DB::raw('SUM(ek.victimId = ' . $playerId . ') AS deaths'),
                DB::raw('SUM(ek.killerId = ' . $playerId . ' AND ek.headshot = 1) AS headshots'),
                DB::raw('IFNULL(ROUND(SUM(ek.killerId = ' . $playerId . ') / IF(SUM(ek.victimId = ' . $playerId . ')=0,1,SUM(ek.victimId = ' . $playerId . ')),2),0) AS kpd')
            )
            ->where(function ($q) use ($playerId) {
                $q->where('ek.killerId', $playerId)->orWhere('ek.victimId', $playerId);
            })
            ->groupBy('p.playerId', 'p.lastName', 'p.country', 'p.flag')
            ->having('kills', '>', 0)
            ->orderByDesc('kills')
            ->limit(50)
            ->get();

        $topKillers = DB::table('hlstats_Events_Frags as ek')
            ->join('hlstats_Players as p', 'ek.killerId', '=', 'p.playerId')
            ->select(
                'p.playerId', 'p.lastName', 'p.country', 'p.flag',
                DB::raw('SUM(ek.victimId = ' . $playerId . ') AS kills'),
                DB::raw('SUM(ek.killerId = ' . $playerId . ') AS deaths'),
                DB::raw('SUM(ek.victimId = ' . $playerId . ' AND ek.headshot = 1) AS headshots'),
                DB::raw('IFNULL(ROUND(SUM(ek.victimId = ' . $playerId . ') / IF(SUM(ek.killerId = ' . $playerId . ')=0,1,SUM(ek.killerId = ' . $playerId . ')),2),0) AS kpd')
            )
            ->where(function ($q) use ($playerId) {
                $q->where('ek.killerId', $playerId)->orWhere('ek.victimId', $playerId);
            })
            ->groupBy('p.playerId', 'p.lastName', 'p.country', 'p.flag')
            ->having('kills', '>', 0)
            ->orderByDesc('kills')
            ->limit(50)
            ->get();

        // Teams (from ChangeTeam events)
        $numTeamJoins = max(1, DB::table('hlstats_Events_ChangeTeam')
            ->where('playerId', $playerId)->count());

        $playerTeams = DB::table('hlstats_Events_ChangeTeam as ct')
            ->leftJoin('hlstats_Teams as t', function ($join) use ($player) {
                $join->on('ct.team', '=', 't.code')
                     ->where('t.game', '=', $player->game);
            })
            ->where('ct.playerId', $playerId)
            ->where(function ($q) {
                $q->where('t.hidden', '<>', '1')->orWhereNull('t.hidden');
            })
            ->select(
                DB::raw('IFNULL(t.name, ct.team) AS name'),
                DB::raw('COUNT(ct.id) AS teamcount'),
                DB::raw('ROUND(COUNT(ct.id) / ' . $numTeamJoins . ' * 100, 2) AS percent')
            )
            ->groupBy('ct.team', 't.name')
            ->orderByDesc('teamcount')
            ->get();

        // Player Actions (PlayerActions + PlayerPlayerActions UNION)
        $playerActions = DB::table('hlstats_Actions as a')
            ->join('hlstats_Events_PlayerActions as pa', 'pa.actionId', '=', 'a.id')
            ->where('pa.playerId', $playerId)
            ->select(
                'a.code', 'a.description',
                DB::raw('COUNT(pa.id) AS obj_count'),
                DB::raw('SUM(pa.bonus) AS obj_bonus')
            )
            ->groupBy('a.id', 'a.code', 'a.description')
            ->orderByDesc('obj_count')
            ->get();

        $playerPlayerActions = DB::table('hlstats_Actions as a')
            ->join('hlstats_Events_PlayerPlayerActions as ppa', 'ppa.actionId', '=', 'a.id')
            ->where('ppa.playerId', $playerId)
            ->select(
                'a.code', 'a.description',
                DB::raw('COUNT(ppa.id) AS obj_count'),
                DB::raw('SUM(ppa.bonus) AS obj_bonus')
            )
            ->groupBy('a.id', 'a.code', 'a.description')
            ->orderByDesc('obj_count')
            ->get();

        // Servers performance
        $playerServers = DB::table('hlstats_Events_Frags as f')
            ->join('hlstats_Servers as s', 'f.serverId', '=', 's.serverId')
            ->where(function ($q) use ($playerId) {
                $q->where('f.killerId', $playerId)->orWhere('f.victimId', $playerId);
            })
            ->select(
                's.name AS server',
                DB::raw('SUM(f.killerId = ' . $playerId . ') AS kills'),
                DB::raw('SUM(f.victimId = ' . $playerId . ') AS deaths'),
                DB::raw('ROUND(SUM(f.killerId = ' . $playerId . ') / ' . $totalKills . ' * 100, 2) AS kpercent'),
                DB::raw('IFNULL(ROUND(SUM(f.killerId = ' . $playerId . ') / IF(SUM(f.victimId = ' . $playerId . ')=0,1,SUM(f.victimId = ' . $playerId . ')),2),0) AS kpd'),
                DB::raw('SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) AS headshots'),
                DB::raw('IFNULL(ROUND(SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) / IF(SUM(f.killerId = ' . $playerId . ')=0,1,SUM(f.killerId = ' . $playerId . ')),2),0) AS hpk'),
                DB::raw('ROUND(SUM(f.killerId = ' . $playerId . ' AND f.headshot=1) / ' . $totalHeadshots . ' * 100, 2) AS hpercent')
            )
            ->groupBy('f.serverId', 's.name')
            ->orderByDesc('kills')
            ->get();

        // Rank based on total kills
        $rank = DB::table('hlstats_Ranks')
            ->where('game', $player->game)
            ->where('minKills', '<=', $player->kills)
            ->orderByDesc('minKills')
            ->first();

        // Favorite server (most visited)
        $favoriteServer = DB::table('hlstats_Events_Connects as ec')
            ->join('hlstats_Servers as s', 'ec.serverId', '=', 's.serverId')
            ->where('ec.playerId', $playerId)
            ->selectRaw('s.name as server_name, COUNT(*) as cnt')
            ->groupBy('ec.serverId', 's.name')
            ->orderByDesc('cnt')
            ->first();

        // Awards count
        $awardsCount = DB::table('hlstats_Players_Awards')
            ->where('playerId', $playerId)
            ->count();

        // Online status (Livestats is MEMORY engine, may be empty when daemon is stopped)
        $isOnline = DB::table('hlstats_Livestats')
            ->where('player_id', $playerId)
            ->exists();

        // Live ping (only meaningful when player is online)
        $livePing = $isOnline
            ? (int) DB::table('hlstats_Livestats')->where('player_id', $playerId)->value('ping')
            : 0;

        // Steam unique ID (raw string, e.g. STEAM_0:1:12345)
        $steamUniqueId = DB::table('hlstats_PlayerUniqueIds')
            ->where('playerId', $playerId)
            ->value('uniqueId');

        // Active ban check
        $isBanned = DB::table('hlstats_Bans')
            ->where('playerId', $playerId)
            ->where(function ($q) {
                $q->whereNull('expires')->orWhere('expires', '>', now());
            })
            ->exists();

        // All ranks for this game (for progression display)
        $allRanks = DB::table('hlstats_Ranks')
            ->where('game', $player->game)
            ->orderBy('minKills')
            ->get();

        // Next rank
        $nextRank = DB::table('hlstats_Ranks')
            ->where('game', $player->game)
            ->where('minKills', '>', $player->kills)
            ->orderBy('minKills')
            ->first();

        // Aliases (player names used)
        $aliases = DB::table('hlstats_PlayerNames')
            ->where('playerId', $playerId)
            ->orderByDesc('connection_time')
            ->get();

        // Ribbons earned
        $ribbons = DB::table('hlstats_Players_Ribbons as pr')
            ->join('hlstats_Ribbons as r', 'r.ribbonId', '=', 'pr.ribbonId')
            ->where('pr.playerId', $playerId)
            ->where('pr.game', $player->game)
            ->select('r.ribbonName', 'r.image')
            ->get();

        // Global awards (weapon/action-based)
        $playerGlobalAwards = DB::table('hlstats_Players_Awards as pa')
            ->join('hlstats_Awards as a', 'a.awardId', '=', 'pa.awardId')
            ->where('pa.playerId', $playerId)
            ->where('pa.game', $player->game)
            ->select('a.name', 'a.verb', 'a.awardType', 'pa.count', 'pa.awardTime')
            ->orderByDesc('pa.awardTime')
            ->get();

        // Rank history (last 20 days with distinct rank transitions)
        $rankHistory = DB::table('hlstats_Players_History')
            ->where('playerId', $playerId)
            ->orderByDesc('eventTime')
            ->select('eventTime', 'kills', 'skill')
            ->limit(60)
            ->get();

        return compact(
            'player', 'topWeapons', 'topMaps', 'topVictims', 'topKillers',
            'rank', 'favoriteServer', 'awardsCount',
            'isOnline', 'livePing', 'steamUniqueId', 'isBanned',
            'allRanks', 'nextRank', 'aliases', 'ribbons', 'playerGlobalAwards', 'rankHistory',
            'playerTeams', 'playerActions', 'playerPlayerActions', 'playerServers'
        );
    }

    /**
     * Get skill history for a player over the last N days.
     */
    public function getSkillHistory(int $playerId, int $days = 30): array
    {
        $since = now()->subDays($days)->toDateString();

        $events = DB::table('hlstats_Players_History')
            ->where('playerId', $playerId)
            ->where('eventTime', '>=', $since)
            ->orderBy('eventTime')
            ->select('eventTime', 'skill')
            ->get();

        return $events->map(function ($e) {
            return [
                'time'  => $e->eventTime,
                'skill' => (int) $e->skill,
            ];
        })->toArray();
    }

    /**
     * Get top weapons for a game.
     */
    public function getTopWeapons(string $game, int $limit = 10): Collection
    {
        return DB::table('hlstats_Weapons')
            ->where('game', $game)
            ->orderByDesc('kills')
            ->limit($limit)
            ->get();
    }

    /**
     * Get players currently on a server.
     */
    public function getServerPlayers(int $serverId): Collection
    {
        return DB::table('hlstats_Livestats as ls')
            ->join('hlstats_Players as p', 'ls.player_id', '=', 'p.playerId')
            ->where('ls.server_id', $serverId)
            ->select('p.*', 'ls.kills as live_kills', 'ls.deaths as live_deaths', 'ls.headshots as live_hs', 'ls.ping', 'ls.skill as live_skill')
            ->get();
    }

    /**
     * Get activity chart data for a server (hourly kill counts).
     */
    public function getActivityChart(int $serverId, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $data = DB::table('hlstats_Events_Frags')
            ->where('serverId', $serverId)
            ->where('eventTime', '>=', $since)
            ->selectRaw("DATE_FORMAT(eventTime, '%Y-%m-%d %H:00:00') as hour, COUNT(*) as kills")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'labels' => $data->pluck('hour')->toArray(),
            'kills'  => $data->pluck('kills')->toArray(),
        ];
    }

    /**
     * Get daily awards for a game on a specific date.
     */
    public function getDailyAwards(string $game, Carbon $date): array
    {
        return Award::with('dailyWinner')
            ->where('game', $game)
            ->get()
            ->toArray();
    }

    public function getPlayerEvents(int $playerId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $sql = "
            SELECT 'Kill' AS eventType, eventTime,
                CONCAT('Killed ', v.lastName, ' with ', weapon, IF(headshot=1,' (headshot)','')) AS eventDesc,
                IFNULL(s.name,'Unknown') AS serverName, ef.map
            FROM hlstats_Events_Frags ef
            LEFT JOIN hlstats_Players v ON v.playerId = ef.victimId
            LEFT JOIN hlstats_Servers s ON s.serverId = ef.serverId
            WHERE ef.killerId = ?

            UNION ALL

            SELECT 'Death' AS eventType, eventTime,
                CONCAT('Killed by ', k.lastName, ' with ', weapon) AS eventDesc,
                IFNULL(s.name,'Unknown') AS serverName, ef.map
            FROM hlstats_Events_Frags ef
            LEFT JOIN hlstats_Players k ON k.playerId = ef.killerId
            LEFT JOIN hlstats_Servers s ON s.serverId = ef.serverId
            WHERE ef.victimId = ?

            UNION ALL

            SELECT 'Connect' AS eventType, eventTime,
                'Connected to the server' AS eventDesc,
                IFNULL(s.name,'Unknown') AS serverName, ec.map
            FROM hlstats_Events_Connects ec
            LEFT JOIN hlstats_Servers s ON s.serverId = ec.serverId
            WHERE ec.playerId = ?

            UNION ALL

            SELECT 'Disconnect' AS eventType, eventTime,
                'Left the game' AS eventDesc,
                IFNULL(s.name,'Unknown') AS serverName, ed.map
            FROM hlstats_Events_Disconnects ed
            LEFT JOIN hlstats_Servers s ON s.serverId = ed.serverId
            WHERE ed.playerId = ?

            ORDER BY eventTime DESC
        ";

        $countSql = "SELECT COUNT(*) AS cnt FROM ($sql) AS combined";
        $total = \Illuminate\Support\Facades\DB::selectOne($countSql, [$playerId, $playerId, $playerId, $playerId])->cnt ?? 0;

        $page    = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset  = ($page - 1) * $perPage;

        $rows = \Illuminate\Support\Facades\DB::select($sql . " LIMIT $perPage OFFSET $offset", [$playerId, $playerId, $playerId, $playerId]);

        return new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        ]);
    }

    public function getPlayerSessions(int $playerId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $total = \Illuminate\Support\Facades\DB::table('hlstats_Players_History')
            ->where('playerId', $playerId)
            ->count();

        $page   = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;

        $rows = \Illuminate\Support\Facades\DB::select("
            SELECT
                eventTime,
                skill_change,
                skill,
                kills,
                deaths,
                headshots,
                suicides,
                teamkills,
                connection_time,
                kill_streak,
                ROUND(kills / IF(deaths=0,1,deaths), 2) AS kpd,
                ROUND(headshots / IF(kills=0,1,kills), 2) AS hpk
            FROM hlstats_Players_History
            WHERE playerId = ?
            ORDER BY eventTime DESC
            LIMIT $perPage OFFSET $offset
        ", [$playerId]);

        return new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        ]);
    }

    public function getPlayerAwards(int $playerId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $total = \Illuminate\Support\Facades\DB::selectOne("
            SELECT COUNT(DISTINCT a.awardId) AS cnt
            FROM hlstats_Players_Awards pa
            JOIN hlstats_Awards a ON a.awardId = pa.awardId
            WHERE pa.playerId = ?
        ", [$playerId])->cnt ?? 0;

        $page   = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;

        $rows = \Illuminate\Support\Facades\DB::select("
            SELECT
                MAX(pa.awardTime) AS awardTime,
                a.name,
                a.verb,
                COUNT(pa.awardId) AS count,
                a.awardId
            FROM hlstats_Players_Awards pa
            JOIN hlstats_Awards a ON a.awardId = pa.awardId
            WHERE pa.playerId = ?
            GROUP BY a.awardId, a.name, a.verb
            ORDER BY awardTime DESC
            LIMIT $perPage OFFSET $offset
        ", [$playerId]);

        return new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        ]);
    }

    public function getPlayerChat(int $playerId, ?string $filter, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $where  = 'ec.playerId = ?';
        $params = [$playerId];

        if ($filter) {
            $where   .= " AND MATCH(ec.message) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $filter;
        }

        $total = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM hlstats_Events_Chat ec WHERE $where",
            $params
        )->cnt ?? 0;

        $page   = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;

        $rows = \Illuminate\Support\Facades\DB::select("
            SELECT
                ec.eventTime,
                CASE ec.message_mode
                    WHEN 2 THEN CONCAT('(Team) ', ec.message)
                    WHEN 3 THEN CONCAT('(Squad) ', ec.message)
                    ELSE ec.message
                END AS message,
                IFNULL(s.name,'Unknown') AS serverName,
                ec.map
            FROM hlstats_Events_Chat ec
            LEFT JOIN hlstats_Servers s ON s.serverId = ec.serverId
            WHERE $where
            ORDER BY ec.eventTime DESC
            LIMIT $perPage OFFSET $offset
        ", $params);

        return new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        ]);
    }
}

