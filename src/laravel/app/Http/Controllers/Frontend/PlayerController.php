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
use App\Services\StatsService;
use App\Services\SteamService;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function __construct(
        private StatsService $stats,
        private SteamService $steam,
        private ThemeService $theme,
    ) {}

    public function index(Request $request)
    {
        $game    = $request->query('game', '');
        $search  = $request->query('search');
        $sort    = $request->query('sort', 'skill');
        $view    = $request->query('view', 'total');
        $country = $request->query('country');
        $period  = (int) $request->query('period', 0);
        if (!in_array($period, [0, 1, 2, 3, 4])) {
            $period = 0;
        }

        $players = $this->stats->getTopPlayers(
            game: $game,
            perPage: 50,
            filters: array_filter(['search' => $search, 'country' => $country]),
            sort: $sort,
            period: $period,
        );

        $maxSkill = $players->first()?->skill ?? 1;

        $playerMarkersQuery = DB::table('hlstats_Players')
            ->where('hideranking', 0)
            ->whereNotNull('lat')->where('lat', '!=', 0)
            ->whereNotNull('lng')->where('lng', '!=', 0);
        if ($game) {
            $playerMarkersQuery->where('game', $game);
        }
        $playerMarkers = $playerMarkersQuery
            ->orderByDesc('skill')
            ->limit(500)
            ->get(['lat', 'lng', 'lastName', 'country'])
            ->map(fn($p) => [
                'lat'     => (float)$p->lat,
                'lng'     => (float)$p->lng,
                'name'    => $p->lastName,
                'country' => $p->country,
            ])
            ->toArray();

        $tileUrl = $this->theme->getCssVariables($this->theme->getActive());
        $theme   = $this->theme->getActive();
        $tileUrl = $theme['charts']['map-tiles'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

        return view('frontend.players.index', compact('players', 'game', 'search', 'sort', 'view', 'maxSkill', 'country', 'playerMarkers', 'tileUrl', 'period'));
    }

    public function show(int $id)
    {
        $profile  = $this->stats->getPlayerProfile($id);
        $player   = $profile['player'];

        $realgame = DB::table('hlstats_Games')->where('code', $player->game)->value('realgame') ?? $player->game;

        // Skill history for chart
        $skillHistory = $this->stats->getSkillHistory($id, 30);
        $chartLabels  = array_column($skillHistory, 'time');
        $chartData    = array_column($skillHistory, 'skill');

        // Steam avatar — use uniqueId already fetched in StatsService
        $steamId64 = null;
        $avatar    = null;
        $steamUniqueId = $profile['steamUniqueId'] ?? null;
        if ($steamUniqueId) {
            $steamId64 = $this->steam->steamId32to64($steamUniqueId);
            $avatar    = $this->steam->getAvatar($steamId64);
        }

        return view('frontend.players.show', array_merge($profile, compact('chartLabels', 'chartData', 'avatar', 'steamId64', 'realgame')));
    }

    private function loadPlayerBasic(int $id): object
    {
        $player = \Illuminate\Support\Facades\DB::table('hlstats_Players')
            ->where('playerId', $id)
            ->first(['playerId', 'lastName', 'game', 'flag', 'country', 'kills', 'deaths', 'skill']);
        abort_if(!$player, 404);
        return $player;
    }

    public function events(int $id, \Illuminate\Http\Request $request)
    {
        $player   = $this->loadPlayerBasic($id);
        $realgame = DB::table('hlstats_Games')->where('code', $player->game)->value('realgame') ?? $player->game;
        $events   = $this->stats->getPlayerEvents($id);
        return view('frontend.players.events', compact('player', 'events', 'realgame'));
    }

    public function sessions(int $id)
    {
        $player   = $this->loadPlayerBasic($id);
        $sessions = $this->stats->getPlayerSessions($id);
        return view('frontend.players.sessions', compact('player', 'sessions'));
    }

    public function awards(int $id)
    {
        $player = $this->loadPlayerBasic($id);
        $awards = $this->stats->getPlayerAwards($id);
        return view('frontend.players.awards', compact('player', 'awards'));
    }

    public function chat(int $id, \Illuminate\Http\Request $request)
    {
        $player   = $this->loadPlayerBasic($id);
        $realgame = DB::table('hlstats_Games')->where('code', $player->game)->value('realgame') ?? $player->game;
        $filter   = $request->query('filter');
        $chat     = $this->stats->getPlayerChat($id, $filter);
        return view('frontend.players.chat', compact('player', 'chat', 'filter', 'realgame'));
    }
}
