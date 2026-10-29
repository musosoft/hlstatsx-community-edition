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
use App\Models\Option;
use Illuminate\Http\Request;

class AdminOptionsController extends Controller
{
    private const GROUPS = [
        'Site Settings' => [
            'sitename'           => ['label' => 'Site Name',                        'type' => 'text'],
            'siteurl'            => ['label' => 'Site URL',                         'type' => 'text'],
            'contact'            => ['label' => 'Contact URL',                      'type' => 'text'],
            'forum_address'      => ['label' => 'Forum URL',                        'type' => 'text'],
            'sourcebans_address' => ['label' => 'SourceBans URL',                   'type' => 'text'],
            'steam_url'          => ['label' => 'Steam URL',                        'type' => 'text'],
            'discord_url'        => ['label' => 'Discord URL',                      'type' => 'text'],
            'nav_globalchat'     => ['label' => 'Show Chat nav-link',               'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
            'nav_cheaters'       => ['label' => 'Show Banned Players nav-link',     'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
            'nav_steam'          => ['label' => 'Show Steam link',                  'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
            'nav_discord'        => ['label' => 'Show Discord link',                'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
            'nav_lang_switcher'  => ['label' => 'Show language switcher (EN / FR)', 'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
        ],
        'Awards Settings' => [
            'gamehome_show_awards' => ['label' => 'Show daily awards on game homepage', 'type' => 'select', 'options' => ['1' => 'Show', '0' => 'Hide']],
            'awarddailycols'       => ['label' => 'Daily Awards: column count',         'type' => 'text'],
            'awardglobalcols'      => ['label' => 'Global Awards: column count',        'type' => 'text'],
            'awardrankscols'       => ['label' => 'Player Ranks: column count',         'type' => 'text'],
            'awardribbonscols'     => ['label' => 'Ribbons: column count',              'type' => 'text'],
        ],
        'Ranking Settings' => [
            'rankingtype'    => ['label' => '*Ranking type',                                       'type' => 'select', 'options' => ['skill' => 'Skill', 'kills' => 'Kills']],
            'MinActivity'    => ['label' => '*Hide players inactive for N days (default 28)',      'type' => 'text'],
            'SkillMaxChange' => ['label' => '*Max skill points per frag (default 25)',             'type' => 'text'],
            'SkillMinChange' => ['label' => '*Min skill points per frag (default 2)',              'type' => 'text'],
            'PlayerMinKills' => ['label' => '*Min kills before regular points (default 50)',       'type' => 'text'],
            'SkillRatioCap'  => ['label' => '*Cap gained skill with ratio',                        'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
        ],
        'Daemon Settings' => [
            'Mode'                   => ['label' => '*Player tracking mode', 'type' => 'select', 'options' => ['Normal' => 'Steam ID (recommended)', 'NameTrack' => 'Player Name', 'LAN' => 'IP Address']],
            'AllowOnlyConfigServers' => ['label' => '*Allow only configured servers',          'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'DeleteDays'             => ['label' => '*Delete event history older than N days', 'type' => 'text'],
            'DNSResolveIP'           => ['label' => '*Resolve player IPs to hostnames',        'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'DNSTimeout'             => ['label' => '*DNS timeout (seconds)',                   'type' => 'text'],
            'MailTo'                 => ['label' => '*Email for DB errors',                     'type' => 'text'],
            'GlobalBanning'          => ['label' => '*Global banning across all servers',      'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'LogChat'                => ['label' => '*Log player chat',                         'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'LogChatAdmins'          => ['label' => '*Log admin chat',                          'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'Rcon'                   => ['label' => '*Allow Rcon commands',                     'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'TrackStatsTrend'        => ['label' => '*Track daily stats trend',                 'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
            'UseTimestamp'           => ['label' => '*Use log-provided timestamps',             'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
        ],
        'Proxy Settings' => [
            'Proxy_Key'     => ['label' => '*Proxy key (empty to disable)',       'type' => 'text'],
            'Proxy_Daemons' => ['label' => '*Daemon list (ip:port,ip:port,...)',  'type' => 'text'],
        ],
        'Paths' => [
            'map_dlurl' => ['label' => 'Map Download URL (%MAP%, %GAME%)', 'type' => 'text'],
        ],
        'Graph Colors' => [
            'graphbg_load'   => ['label' => 'Server Load graph background (RRGGBB)', 'type' => 'text'],
            'graphtxt_load'  => ['label' => 'Server Load graph text (RRGGBB)',       'type' => 'text'],
            'graphbg_trend'  => ['label' => 'Trend graph background (RRGGBB)',       'type' => 'text'],
            'graphtxt_trend' => ['label' => 'Trend graph text (RRGGBB)',             'type' => 'text'],
        ],
        'Map Settings' => [
            'map_region' => ['label' => 'Default map region', 'type' => 'select', 'options' => [
                'world'          => 'World',
                'europe'         => 'Europe',
                'north_america'  => 'North America',
                'south_america'  => 'South America',
                'africa'         => 'Africa',
                'asia'           => 'Asia',
                'oceania'        => 'Oceania',
                'france'         => 'France',
                'germany'        => 'Germany',
                'spain'          => 'Spain',
                'united_kingdom' => 'United Kingdom',
                'italy'          => 'Italy',
                'belgium'        => 'Belgium',
                'netherlands'    => 'Netherlands',
                'switzerland'    => 'Switzerland',
                'sweden'         => 'Sweden',
                'denmark'        => 'Denmark',
                'norway'         => 'Norway',
                'finland'        => 'Finland',
                'poland'         => 'Poland',
                'austria'        => 'Austria',
            ]],
        ],
    ];

    public const REGION_COORDS = [
        'world'          => ['lat' => 20,    'lng' => 0,     'zoom' => 2],
        'europe'         => ['lat' => 54,    'lng' => 15,    'zoom' => 4],
        'north_america'  => ['lat' => 54,    'lng' => -105,  'zoom' => 3],
        'south_america'  => ['lat' => -15,   'lng' => -55,   'zoom' => 3],
        'africa'         => ['lat' => 7,     'lng' => 20,    'zoom' => 3],
        'asia'           => ['lat' => 35,    'lng' => 100,   'zoom' => 3],
        'oceania'        => ['lat' => -25,   'lng' => 135,   'zoom' => 4],
        'france'         => ['lat' => 46.6,  'lng' => 1.9,   'zoom' => 5],
        'germany'        => ['lat' => 51,    'lng' => 10,    'zoom' => 6],
        'spain'          => ['lat' => 40,    'lng' => -3,    'zoom' => 6],
        'united_kingdom' => ['lat' => 54,    'lng' => -2,    'zoom' => 5],
        'italy'          => ['lat' => 42,    'lng' => 12,    'zoom' => 6],
        'belgium'        => ['lat' => 50.5,  'lng' => 4.5,   'zoom' => 7],
        'netherlands'    => ['lat' => 52.3,  'lng' => 5.3,   'zoom' => 7],
        'switzerland'    => ['lat' => 46.8,  'lng' => 8.2,   'zoom' => 7],
        'sweden'         => ['lat' => 62,    'lng' => 15,    'zoom' => 5],
        'denmark'        => ['lat' => 56,    'lng' => 10,    'zoom' => 7],
        'norway'         => ['lat' => 65,    'lng' => 13,    'zoom' => 5],
        'finland'        => ['lat' => 64,    'lng' => 26,    'zoom' => 5],
        'poland'         => ['lat' => 52,    'lng' => 20,    'zoom' => 6],
        'austria'        => ['lat' => 47.5,  'lng' => 14,    'zoom' => 7],
    ];

    public function index()
    {
        $options = Option::pluck('value', 'keyname');
        return view('admin.options.index', compact('options'));
    }

    public function update(Request $request)
    {
        foreach (self::GROUPS as $options) {
            foreach ($options as $key => $meta) {
                $value = $request->input($key) ?? '';
                Option::set($key, $value);
            }
        }
        return redirect()->route('admin.options.index')->with('success', 'Options updated.');
    }

    public static function groups(): array
    {
        return self::GROUPS;
    }
}
