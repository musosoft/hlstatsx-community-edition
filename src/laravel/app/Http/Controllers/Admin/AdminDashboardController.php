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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ban;
use App\Models\Clan;
use App\Models\Game;
use App\Models\Player;
use App\Models\Server;
use App\Services\StatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminDashboardController extends Controller
{
    public function __construct(private StatsService $stats) {}

    public function index()
    {
        $period = (int) request()->integer('period', 7);
        if (!in_array($period, [7, 30, 90], true)) {
            $period = 7;
        }

        $stats = [
            'players' => Player::count(),
            'clans'   => Clan::count(),
            'servers' => Server::count(),
            'games'   => Game::count(),
            'bans'    => Ban::where(function ($q) {
                $q->whereNull('expires')->orWhere('expires', '>', now());
            })->count(),
        ];

        $globalStats = $this->stats->getGlobalStats();
        $versionInfo = $this->getVersionInfo();
        $dashboardCharts = [
            'killsPerDay' => $this->getDailyEventSeries('hlstats_Events_Frags', 'eventTime', $period),
            'connectionsPerDay' => $this->getDailyEventSeries('hlstats_Events_Connects', 'eventTime', $period),
            'playersByGame' => $this->getPlayersByGameChart(6),
            'bansStatus' => $this->getBansStatusChart(),
        ];

        return view('admin.dashboard', compact('stats', 'globalStats', 'versionInfo', 'dashboardCharts', 'period'));
    }

    private function getDailyEventSeries(string $table, string $dateColumn, int $days = 7): array
    {
        $days = max(1, $days);
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = DB::table($table)
            ->selectRaw("DATE($dateColumn) as day, COUNT(*) as total")
            ->where($dateColumn, '>=', $start)
            ->groupByRaw("DATE($dateColumn)")
            ->orderByRaw("DATE($dateColumn)")
            ->get();

        $totalsByDay = $rows
            ->mapWithKeys(fn ($row) => [(string) $row->day => (int) $row->total])
            ->all();

        $labels = [];
        $data = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = now()->subDays($offset);
            $key = $day->toDateString();

            $labels[] = $day->isoFormat('DD/MM');
            $data[] = $totalsByDay[$key] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getPlayersByGameChart(int $limit = 6): array
    {
        $rows = DB::table('hlstats_Players as p')
            ->leftJoin('hlstats_Games as g', 'g.code', '=', 'p.game')
            ->select('p.game', 'g.name')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('p.game', 'g.name')
            ->orderByDesc('total')
            ->limit(max(1, $limit))
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $row->name ?: $row->game)->values()->all(),
            'data' => $rows->map(fn ($row) => (int) $row->total)->values()->all(),
        ];
    }

    private function getBansStatusChart(): array
    {
        $activeCount = Ban::where(function ($query) {
            $query->whereNull('expires')->orWhere('expires', '>', now());
        })->count();

        $expiredCount = Ban::whereNotNull('expires')
            ->where('expires', '<=', now())
            ->count();

        return [
            'labels' => [__('Active bans'), __('Expired bans')],
            'data' => [(int) $activeCount, (int) $expiredCount],
        ];
    }

    private function getInstalledVersion(): string
    {
        // Use a dedicated key — the legacy Perl daemon overwrites `version`
        // at every startup with its own hardcoded value (currently 2.5.9).
        $version = DB::table('hlstats_Options')
            ->where('keyname', 'webapp_version')
            ->value('value');

        return $version ? trim($version) : 'unknown';
    }

    private function getVersionInfo(): array
    {
        $installed = $this->getInstalledVersion();

        $latest = Cache::remember('admin_update_check', 3600, function () {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'hlstatsx-ce-update-checker'])
                    ->get('https://api.github.com/repos/Royal-Multi-Gamers/hlstatsx-community-edition-laravel/releases/latest');

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable) {}

            return null;
        });

        if ($latest === null) {
            return [
                'installed' => $installed,
                'latest'    => null,
                'upToDate'  => null,
            ];
        }

        $latestTag = ltrim($latest['tag_name'] ?? '', 'v');
        $upToDate  = version_compare($installed, $latestTag, '>=');

        return [
            'installed'   => $installed,
            'latest'      => $latest,
            'latestTag'   => $latestTag,
            'upToDate'    => $upToDate,
        ];
    }
}
