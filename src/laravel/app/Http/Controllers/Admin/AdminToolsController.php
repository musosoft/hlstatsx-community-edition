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

class AdminToolsController extends Controller
{
    public function index()
    {
        $games = Game::ordered()->get();
        return view('admin.tools.index', compact('games'));
    }

    public function optimizeDb()
    {
        $tables = [
            'hlstats_Players', 'hlstats_Events_Frags', 'hlstats_Events_Chat',
            'hlstats_Events_Connects', 'hlstats_Events_Disconnects',
            'hlstats_Events_Suicides', 'hlstats_Events_TeamBonuses',
            'hlstats_Events_PlayerActions', 'hlstats_Events_PlayerPlayerActions',
            'hlstats_PlayerWeapons', 'hlstats_Awards', 'hlstats_Clans',
        ];
        foreach ($tables as $table) {
            DB::statement("OPTIMIZE TABLE `{$table}`");
        }
        return redirect()->route('admin.tools.index')->with('success', 'Database optimized (' . count($tables) . ' tables).');
    }

    public function resetGame(Request $request)
    {
        $data = $request->validate([
            'game'    => ['required', 'string', 'exists:hlstats_Games,code'],
            'confirm' => ['required', 'in:RESET'],
        ]);

        $game = $data['game'];

        // Reset player stats for this game
        DB::table('hlstats_Players')
            ->where('game', $game)
            ->update([
                'skill'          => 1000,
                'kills'          => 0,
                'deaths'         => 0,
                'suicides'       => 0,
                'headshots'      => 0,
                'shots'          => 0,
                'hits'           => 0,
                'teamkills'      => 0,
                'kill_streak'    => 0,
                'death_streak'   => 0,
                'connection_time'=> 0,
            ]);

        // Delete events for this game's servers
        $serverIds = DB::table('hlstats_Servers')
            ->where('game', $game)
            ->pluck('serverId');

        foreach (['hlstats_Events_Frags', 'hlstats_Events_Chat', 'hlstats_Events_Connects',
                  'hlstats_Events_Disconnects', 'hlstats_Events_Suicides',
                  'hlstats_Events_PlayerActions', 'hlstats_Events_PlayerPlayerActions',
                  'hlstats_Events_TeamBonuses'] as $table) {
            DB::table($table)->whereIn('serverId', $serverIds)->delete();
        }

        return redirect()->route('admin.tools.index')->with('success', "Stats for game [{$game}] have been reset.");
    }

    public function deletePlayers(Request $request)
    {
        $data = $request->validate([
            'game'    => ['required', 'string', 'exists:hlstats_Games,code'],
            'confirm' => ['required', 'in:DELETE'],
        ]);

        $deleted = DB::table('hlstats_Players')
            ->where('game', $data['game'])
            ->where('kills', 0)
            ->where('deaths', 0)
            ->delete();

        return redirect()->route('admin.tools.index')->with('success', "Deleted {$deleted} inactive players from game [{$data['game']}].");
    }

    public function partialReset(Request $request)
    {
        $game = $request->input('game', '');
        $ops  = $request->input('ops', []);

        if (empty($ops)) {
            return redirect()->route('admin.tools.index')->with('error', 'Aucune opÃ©ration sÃ©lectionnÃ©e.');
        }

        $gameFilter = $game !== '' ? $game : null;

        // Helper closures
        $truncOrDelete = function (string $table, ?string $gameCol = null) use ($gameFilter) {
            if ($gameFilter === null) {
                DB::statement("TRUNCATE TABLE `{$table}`");
            } elseif ($gameCol) {
                DB::table($table)->where($gameCol, $gameFilter)->delete();
            }
        };

        $truncOrDeleteViaServers = function (string $table) use ($gameFilter) {
            if ($gameFilter === null) {
                DB::statement("TRUNCATE TABLE `{$table}`");
            } else {
                $serverIds = DB::table('hlstats_Servers')->where('game', $gameFilter)->pluck('serverId');
                DB::table($table)->whereIn('serverId', $serverIds)->delete();
            }
        };

        $done = [];

        if (in_array('awards', $ops)) {
            $q = DB::table('hlstats_Awards');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['d_winner_id' => null, 'd_winner_count' => null, 'g_winner_id' => null, 'g_winner_count' => null]);
            $truncOrDelete('hlstats_Players_Awards', $gameFilter ? 'game' : null);
            $truncOrDelete('hlstats_Players_Ribbons', $gameFilter ? 'game' : null);
            $done[] = 'RÃ©compenses';
        }
        if (in_array('sessions', $ops)) {
            $truncOrDelete('hlstats_Players_History', 'game');
            $done[] = 'Historique sessions';
        }
        if (in_array('names', $ops)) {
            if ($gameFilter === null) {
                DB::statement('TRUNCATE TABLE `hlstats_PlayerNames`');
            } else {
                $playerIds = DB::table('hlstats_Players')->where('game', $gameFilter)->pluck('playerId');
                DB::table('hlstats_PlayerNames')->whereIn('playerId', $playerIds)->delete();
            }
            $done[] = 'Historique noms';
        }
        if (in_array('names_counts', $ops)) {
            $q = DB::table('hlstats_PlayerNames');
            if ($gameFilter) {
                $playerIds = DB::table('hlstats_Players')->where('game', $gameFilter)->pluck('playerId');
                $q->whereIn('playerId', $playerIds);
            }
            $q->update(['connection_time' => 0, 'numuses' => 0, 'kills' => 0, 'deaths' => 0, 'suicides' => 0, 'headshots' => 0, 'shots' => 0, 'hits' => 0]);
            $done[] = 'Compteurs noms';
        }
        if (in_array('skill', $ops)) {
            $q = DB::table('hlstats_Players');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['skill' => 1000]);
            $done[] = 'Skill joueurs';
        }
        if (in_array('pcounts', $ops)) {
            $q = DB::table('hlstats_Players');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['connection_time' => 0, 'kills' => 0, 'deaths' => 0, 'suicides' => 0, 'shots' => 0, 'hits' => 0, 'headshots' => 0, 'last_skill_change' => 0, 'kill_streak' => 0, 'death_streak' => 0]);
            $done[] = 'Compteurs joueurs';
        }
        if (in_array('scounts', $ops)) {
            $q = DB::table('hlstats_Servers');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['kills' => 0, 'players' => 0, 'rounds' => 0, 'suicides' => 0, 'headshots' => 0, 'bombs_planted' => 0, 'bombs_defused' => 0, 'ct_wins' => 0, 'ts_wins' => 0, 'ct_shots' => 0, 'ct_hits' => 0, 'ts_shots' => 0, 'ts_hits' => 0, 'map_ct_shots' => 0, 'map_ct_hits' => 0, 'map_ts_shots' => 0, 'map_ts_hits' => 0, 'map_rounds' => 0, 'map_ct_wins' => 0, 'map_ts_wins' => 0, 'map_started' => 0, 'map_changes' => 0, 'act_map' => '', 'act_players' => 0]);
            $done[] = 'Compteurs serveurs';
        }
        if (in_array('wcounts', $ops)) {
            $q = DB::table('hlstats_Weapons');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['kills' => 0, 'headshots' => 0]);
            $done[] = 'Compteurs armes';
        }
        if (in_array('acounts', $ops)) {
            $q = DB::table('hlstats_Actions');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['count' => 0]);
            $done[] = 'Compteurs actions';
        }
        if (in_array('mcounts', $ops)) {
            $q = DB::table('hlstats_Maps_Counts');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['kills' => 0, 'headshots' => 0]);
            $done[] = 'Compteurs maps';
        }
        if (in_array('rcounts', $ops)) {
            $q = DB::table('hlstats_Roles');
            if ($gameFilter) $q->where('game', $gameFilter);
            $q->update(['picked' => 0, 'kills' => 0, 'deaths' => 0]);
            $done[] = 'Compteurs rÃ´les';
        }

        // Events
        $eventTables = [
            'ev_admin'        => 'hlstats_Events_Admin',
            'ev_changename'   => 'hlstats_Events_ChangeName',
            'ev_changerole'   => 'hlstats_Events_ChangeRole',
            'ev_changeteam'   => 'hlstats_Events_ChangeTeam',
            'ev_chat'         => 'hlstats_Events_Chat',
            'ev_connects'     => 'hlstats_Events_Connects',
            'ev_disconnects'  => 'hlstats_Events_Disconnects',
            'ev_entries'      => 'hlstats_Events_Entries',
            'ev_frags'        => 'hlstats_Events_Frags',
            'ev_rcon'         => 'hlstats_Events_Rcon',
            'ev_suicides'     => 'hlstats_Events_Suicides',
            'ev_teamkills'    => 'hlstats_Events_Teamkills',
        ];
        foreach ($eventTables as $key => $table) {
            if (in_array($key, $ops)) {
                $truncOrDeleteViaServers($table);
                $done[] = $table;
            }
        }
        if (in_array('ev_latency', $ops)) {
            $truncOrDeleteViaServers('hlstats_Events_Latency');
            $truncOrDeleteViaServers('hlstats_Events_StatsmeLatency');
            $done[] = 'Events Latency';
        }
        if (in_array('ev_statsme', $ops)) {
            $truncOrDeleteViaServers('hlstats_Events_Statsme');
            $truncOrDeleteViaServers('hlstats_Events_Statsme2');
            $done[] = 'Events Statsme';
        }
        if (in_array('ev_statsmetime', $ops)) {
            $truncOrDeleteViaServers('hlstats_Events_StatsmeTime');
            $done[] = 'Events StatsmeTime';
        }
        if (in_array('ev_actions', $ops)) {
            $truncOrDeleteViaServers('hlstats_Events_PlayerActions');
            $truncOrDeleteViaServers('hlstats_Events_PlayerPlayerActions');
            $truncOrDeleteViaServers('hlstats_Events_TeamBonuses');
            $done[] = 'Events Actions';
        }

        // Delete clans + players if requested
        if (in_array('delete_players', $ops)) {
            if ($gameFilter === null) {
                DB::statement('TRUNCATE TABLE `hlstats_PlayerUniqueIds`');
                DB::statement('TRUNCATE TABLE `hlstats_Players`');
                DB::statement('TRUNCATE TABLE `hlstats_Clans`');
            } else {
                DB::table('hlstats_PlayerUniqueIds')
                    ->whereIn('playerId', DB::table('hlstats_Players')->where('game', $gameFilter)->pluck('playerId'))
                    ->delete();
                DB::table('hlstats_Players')->where('game', $gameFilter)->delete();
                DB::table('hlstats_Clans')->where('game', $gameFilter)->delete();
            }
            $done[] = 'Joueurs & Clans supprimÃ©s';
        }

        $scope = $gameFilter ? "jeu [{$gameFilter}]" : 'toute la base';
        return redirect()->route('admin.tools.index')
            ->with('success', 'Reset partiel sur ' . $scope . ' : ' . implode(', ', $done) . '.');
    }

    public function resetCollations(Request $request)
    {
        $charset   = 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';

        $tables = DB::select('SHOW TABLES');
        $dbKey  = 'Tables_in_' . DB::getDatabaseName();

        $done = 0;
        DB::statement("ALTER DATABASE `" . DB::getDatabaseName() . "` DEFAULT CHARACTER SET {$charset} COLLATE {$collation}");

        foreach ($tables as $row) {
            $table = $row->$dbKey;
            DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}");
            $done++;
        }

        return redirect()->route('admin.tools.index')
            ->with('success', "Collations converties en {$charset}/{$collation} sur {$done} tables.");
    }
}

