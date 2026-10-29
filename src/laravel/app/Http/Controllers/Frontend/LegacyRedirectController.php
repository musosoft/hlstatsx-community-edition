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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps the pre-rebase /hlstats.php?mode=… URLs onto the clean routes.
 *
 * Everything answers with a 301 so Google consolidates the legacy URL into the
 * new one instead of keeping both. Modes that no longer exist answer 404 rather
 * than bouncing to the home page: a mass redirect to "/" is treated as a soft
 * 404 by search engines and is what caused the "Page with redirect" reports.
 */
class LegacyRedirectController extends Controller
{
    /**
     * Legacy mode => route name, for listing pages that take no identifier.
     * A ?game= parameter is forwarded as a query string when present.
     */
    private const LISTING_MODES = [
        ''          => 'home',
        'home'      => 'home',
        'main'      => 'home',
        'index'     => 'home',
        'search'    => 'search',
        'players'   => 'players.index',
        'top10'     => 'players.index',
        'clans'     => 'clans.index',
        'servers'   => 'servers.index',
        'livestats' => 'servers.index',
        'weapons'   => 'weapons.index',
        'maps'      => 'maps.index',
        'actions'   => 'actions.index',
        'awards'    => 'awards.index',
        'ribbons'   => 'awards.index',
        'chat'      => 'chat.index',
        'countries' => 'countries.index',
        'bans'      => 'bans.index',
        'cheaters'  => 'bans.index',
        'roles'     => 'roles.index',
        'help'      => 'help',
    ];

    /**
     * Legacy mode => [route name, route parameter, legacy query parameter, numeric?].
     * Both the historical HLstatsX:CE names (playerinfo) and the shorter aliases
     * used by some skins (player) are accepted.
     */
    private const DETAIL_MODES = [
        'playerinfo' => ['players.show', 'id',   'player', true],
        'player'     => ['players.show', 'id',   'player', true],
        'claninfo'   => ['clans.show',   'id',   'clan',   true],
        'clan'       => ['clans.show',   'id',   'clan',   true],
        'serverinfo' => ['servers.show', 'id',   'server', true],
        'server'     => ['servers.show', 'id',   'server', true],
        'awardinfo'  => ['awards.detail', 'id',  'award',  true],
        'weaponinfo' => ['weapons.show', 'code', 'weapon', false],
        'weapon'     => ['weapons.show', 'code', 'weapon', false],
        'roleinfo'   => ['roles.show',   'code', 'role',   false],
        'role'       => ['roles.show',   'code', 'role',   false],
        'mapinfo'    => ['maps.show',    'map',  'map',    false],
        'map'        => ['maps.show',    'map',  'map',    false],
        'gamepage'   => ['game.show',    'code', 'game',   false],
        'game'       => ['game.show',    'code', 'game',   false],
    ];

    public function redirect(Request $request, PlayerSignatureController $signatures): Response
    {
        // ?mode[]=x would otherwise blow up on the string cast.
        $mode = $request->query('mode', '');
        $mode = is_scalar($mode) ? strtolower(trim((string) $mode)) : 'invalid';

        // The forum signature is a real endpoint, not a redirect.
        if ($mode === 'playersig') {
            $player = $request->query('player');

            if (!is_numeric($player)) {
                abort(404);
            }

            return $signatures->show($request, (int) $player);
        }

        if (isset(self::DETAIL_MODES[$mode])) {
            return $this->detailRedirect($request, $mode);
        }

        if (isset(self::LISTING_MODES[$mode])) {
            return $this->listingRedirect($request, $mode);
        }

        // Retired modes (rss, trend, herotracker, …) have no equivalent page.
        abort(404);
    }

    private function detailRedirect(Request $request, string $mode): RedirectResponse
    {
        [$route, $routeParam, $queryParam, $numeric] = self::DETAIL_MODES[$mode];

        $value = $request->query($queryParam);

        if ($value === null || $value === '' || !is_scalar($value)) {
            abort(404);
        }

        if ($numeric) {
            if (!is_numeric($value) || (int) $value <= 0) {
                abort(404);
            }
            $value = (int) $value;
        }

        $params = [$routeParam => $value];

        // Weapons, maps and roles are scoped by game in the new routes.
        $game = $request->query('game');
        if ($game && !$numeric && $route !== 'game.show') {
            $params['game'] = $game;
        }

        return redirect()->route($route, $params, 301);
    }

    private function listingRedirect(Request $request, string $mode): RedirectResponse
    {
        $params = [];

        foreach (['game', 'q', 'sort', 'country'] as $key) {
            $value = $request->query($key);
            if ($value !== null && $value !== '' && is_scalar($value)) {
                $params[$key] = $value;
            }
        }

        return redirect()->route(self::LISTING_MODES[$mode], $params, 301);
    }
}
