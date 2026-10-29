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
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGameController extends Controller
{
    public function index()
    {
        $games = Game::ordered()->get();
        return view('admin.games.index', compact('games'));
    }

    public function create()
    {
        return view('admin.games.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'                    => ['required', 'string', 'max:32', 'unique:hlstats_Games,code'],
            'name'                    => ['required', 'string', 'max:128'],
            'realgame'                => ['nullable', 'string', 'max:32'],
            'sortorder'               => ['nullable', 'integer', 'min:0', 'max:255'],
            'query_url'               => ['nullable', 'url', 'max:255'],
            'query_match_field'       => ['nullable', 'string', 'max:64', 'required_with:query_url'],
            'query_max_players_field' => ['nullable', 'string', 'max:64', 'required_with:query_url'],
        ]);
        $data['hidden'] = $request->boolean('hidden') ? '1' : '0';
        $data['sortorder'] = (int) ($data['sortorder'] ?? 0);
        Game::create($data);
        return redirect()->route('admin.games.index')->with('success', 'Game created.');
    }

    public function edit(string $code)
    {
        $game = Game::findOrFail($code);
        return view('admin.games.edit', compact('game'));
    }

    public function update(Request $request, string $code)
    {
        $game = Game::findOrFail($code);
        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:128'],
            'realgame'                => ['nullable', 'string', 'max:32'],
            'sortorder'               => ['nullable', 'integer', 'min:0', 'max:255'],
            'query_url'               => ['nullable', 'url', 'max:255'],
            'query_match_field'       => ['nullable', 'string', 'max:64', 'required_with:query_url'],
            'query_max_players_field' => ['nullable', 'string', 'max:64', 'required_with:query_url'],
        ]);
        $data['hidden'] = $request->boolean('hidden') ? '1' : '0';
        $data['sortorder'] = (int) ($data['sortorder'] ?? 0);
        $game->update($data);
        return redirect()->route('admin.games.index')->with('success', 'Game updated.');
    }

    public function destroy(string $code)
    {
        $game = Game::findOrFail($code);

        DB::transaction(function () use ($code) {
            $serverIds = DB::table('hlstats_Servers')->where('game', $code)->pluck('serverId');

            // Server-level data
            if ($serverIds->isNotEmpty()) {
                DB::table('hlstats_Servers_Config')->whereIn('serverId', $serverIds)->delete();
                foreach ([
                    'hlstats_Events_Frags', 'hlstats_Events_Chat', 'hlstats_Events_Connects',
                    'hlstats_Events_Disconnects', 'hlstats_Events_Suicides', 'hlstats_Events_Teamkills',
                    'hlstats_Events_PlayerActions', 'hlstats_Events_PlayerPlayerActions',
                    'hlstats_Events_TeamBonuses', 'hlstats_Events_Admin', 'hlstats_Events_ChangeName',
                    'hlstats_Events_ChangeRole', 'hlstats_Events_ChangeTeam', 'hlstats_Events_Entries',
                    'hlstats_Events_Rcon', 'hlstats_Events_Latency', 'hlstats_Events_StatsmeLatency',
                    'hlstats_Events_Statsme', 'hlstats_Events_Statsme2', 'hlstats_Events_StatsmeTime',
                ] as $table) {
                    DB::table($table)->whereIn('serverId', $serverIds)->delete();
                }
                DB::table('hlstats_Servers')->where('game', $code)->delete();
            }

            // Game-level data
            $playerIds = DB::table('hlstats_Players')->where('game', $code)->pluck('playerId');
            if ($playerIds->isNotEmpty()) {
                DB::table('hlstats_PlayerNames')->whereIn('playerId', $playerIds)->delete();
                DB::table('hlstats_PlayerUniqueIds')->whereIn('playerId', $playerIds)->delete();
                DB::table('hlstats_Players_Awards')->where('game', $code)->delete();
                DB::table('hlstats_Players_Ribbons')->where('game', $code)->delete();
                DB::table('hlstats_Players_History')->where('game', $code)->delete();
            }
            DB::table('hlstats_Players')->where('game', $code)->delete();
            DB::table('hlstats_Clans')->where('game', $code)->delete();
            DB::table('hlstats_Weapons')->where('game', $code)->delete();
            DB::table('hlstats_Ranks')->where('game', $code)->delete();
            DB::table('hlstats_Teams')->where('game', $code)->delete();
            DB::table('hlstats_Roles')->where('game', $code)->delete();
            DB::table('hlstats_Actions')->where('game', $code)->delete();
            DB::table('hlstats_Awards')->where('game', $code)->delete();
            DB::table('hlstats_Ribbons')->where('game', $code)->delete();
            DB::table('hlstats_Maps_Counts')->where('game', $code)->delete();
            DB::table('hlstats_Games_Defaults')->where('code', $code)->delete();

            Game::where('code', $code)->delete();
        });

        return redirect()->route('admin.games.index')->with('success', "Game [{$code}] and all related data deleted.");
    }

    public function showDuplicate(string $code)
    {
        $game = Game::findOrFail($code);
        $counts = [
            'weapons'        => DB::table('hlstats_Weapons')->where('game', $code)->count(),
            'ranks'          => DB::table('hlstats_Ranks')->where('game', $code)->count(),
            'teams'          => DB::table('hlstats_Teams')->where('game', $code)->count(),
            'roles'          => DB::table('hlstats_Roles')->where('game', $code)->count(),
            'actions'        => DB::table('hlstats_Actions')->where('game', $code)->count(),
            'awards'         => DB::table('hlstats_Awards')->where('game', $code)->count(),
            'ribbons'        => DB::table('hlstats_Ribbons')->where('game', $code)->count(),
            'server_config'  => DB::table('hlstats_Games_Defaults')->where('code', $code)->count(),
        ];
        return view('admin.games.duplicate', compact('game', 'counts'));
    }

    public function duplicate(Request $request, string $code)
    {
        $source = Game::findOrFail($code);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:hlstats_Games,code', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:128'],
        ]);

        DB::transaction(function () use ($source, $data) {
            $newCode = $data['code'];

            Game::create([
                'code'     => $newCode,
                'name'     => $data['name'],
                'realgame' => $source->realgame,
                'hidden'   => $source->hidden,
                'sortorder' => $source->sortorder,
            ]);

            // Weapons
            DB::table('hlstats_Weapons')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Weapons')->insert([
                    'game'      => $newCode,
                    'code'      => $r->code,
                    'name'      => $r->name,
                    'modifier'  => $r->modifier,
                    'kills'     => 0,
                    'headshots' => 0,
                ]));

            // Ranks
            DB::table('hlstats_Ranks')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Ranks')->insert([
                    'game'     => $newCode,
                    'image'    => $r->image,
                    'minKills' => $r->minKills,
                    'maxKills' => $r->maxKills,
                    'rankName' => $r->rankName,
                ]));

            // Teams
            DB::table('hlstats_Teams')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Teams')->insert([
                    'game'               => $newCode,
                    'code'               => $r->code,
                    'name'               => $r->name,
                    // ENUM('0','1') NOT NULL â€” coerce empty/null to '0'
                    'hidden'             => in_array((string) $r->hidden, ['0', '1'], true) ? (string) $r->hidden : '0',
                    'playerlist_bgcolor' => $r->playerlist_bgcolor,
                    'playerlist_color'   => $r->playerlist_color,
                    'playerlist_index'   => $r->playerlist_index,
                ]));

            // Roles
            DB::table('hlstats_Roles')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Roles')->insert([
                    'game'   => $newCode,
                    'code'   => $r->code,
                    'name'   => $r->name,
                    'hidden' => in_array((string) $r->hidden, ['0', '1'], true) ? (string) $r->hidden : '0',
                    'picked' => 0,
                    'kills'  => 0,
                    'deaths' => 0,
                ]));

            // Actions
            DB::table('hlstats_Actions')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Actions')->insert([
                    'game'                    => $newCode,
                    'code'                    => $r->code,
                    'reward_player'           => $r->reward_player,
                    'reward_team'             => $r->reward_team,
                    'team'                    => $r->team,
                    'description'             => $r->description,
                    // All four are ENUM('0','1') NOT NULL
                    'for_PlayerActions'       => in_array((string) $r->for_PlayerActions, ['0', '1'], true) ? (string) $r->for_PlayerActions : '0',
                    'for_PlayerPlayerActions' => in_array((string) $r->for_PlayerPlayerActions, ['0', '1'], true) ? (string) $r->for_PlayerPlayerActions : '0',
                    'for_TeamActions'         => in_array((string) $r->for_TeamActions, ['0', '1'], true) ? (string) $r->for_TeamActions : '0',
                    'for_WorldActions'        => in_array((string) $r->for_WorldActions, ['0', '1'], true) ? (string) $r->for_WorldActions : '0',
                    'count'                   => 0,
                ]));

            // Awards
            DB::table('hlstats_Awards')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Awards')->insert([
                    'game'           => $newCode,
                    'awardType'      => $r->awardType,
                    'code'           => $r->code,
                    'name'           => $r->name,
                    'verb'           => $r->verb,
                    'd_winner_id'    => 0,
                    'd_winner_count' => 0,
                    'g_winner_id'    => 0,
                    'g_winner_count' => 0,
                ]));

            // Ribbons
            DB::table('hlstats_Ribbons')->where('game', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Ribbons')->insert([
                    'game'       => $newCode,
                    'awardCode'  => $r->awardCode,
                    'awardCount' => $r->awardCount,
                    'special'    => $r->special,
                    'image'      => $r->image,
                    'ribbonName' => $r->ribbonName,
                ]));

            // Server config defaults (Games_Defaults)
            DB::table('hlstats_Games_Defaults')->where('code', $source->code)->get()
                ->each(fn($r) => DB::table('hlstats_Games_Defaults')->insertOrIgnore([
                    'code'      => $newCode,
                    'parameter' => $r->parameter,
                    'value'     => $r->value,
                ]));
        });

        return redirect()->route('admin.games.index')
            ->with('success', "Jeu [{$source->code}] dupliquÃ© en [{$data['code']}] avec succÃ¨s.");
    }
}

