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
use App\Models\Server;
use App\Models\ServerConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminServerConfigController extends Controller
{
    public function index(Request $request)
    {
        $servers  = Server::orderBy('name')->get();
        $serverId = $request->input('server_id', $servers->first()?->serverId);
        $configs  = ServerConfig::where('serverId', $serverId)->orderBy('parameter')->get();
        return view('admin.server-config.index', compact('servers', 'serverId', 'configs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'server_id' => ['required', 'integer', 'exists:hlstats_Servers,serverId'],
            'parameter' => ['required', 'string', 'max:50'],
            'value'     => ['required', 'string', 'max:128'],
        ]);

        ServerConfig::updateOrCreate(
            ['serverId' => $data['server_id'], 'parameter' => $data['parameter']],
            ['value' => $data['value']]
        );

        return redirect()->route('admin.server-config.index', ['server_id' => $data['server_id']])->with('success', 'Config saved.');
    }

    public function update(Request $request, int $id)
    {
        $config = ServerConfig::findOrFail($id);
        $data   = $request->validate([
            'value' => ['required', 'string', 'max:128'],
        ]);
        $config->update($data);
        return redirect()->route('admin.server-config.index', ['server_id' => $config->serverId])->with('success', 'Config updated.');
    }

    public function destroy(int $id)
    {
        $config   = ServerConfig::findOrFail($id);
        $serverId = $config->serverId;
        $config->delete();
        return redirect()->route('admin.server-config.index', ['server_id' => $serverId])->with('success', 'Config deleted.');
    }

    public function populateFromDefaults(Request $request)
    {
        $data = $request->validate([
            'server_id' => ['required', 'integer', 'exists:hlstats_Servers,serverId'],
        ]);

        $server   = Server::findOrFail($data['server_id']);
        $defaults = DB::table('hlstats_Games_Defaults')->where('code', $server->game)->get();

        if ($defaults->isEmpty()) {
            return redirect()->route('admin.server-config.index', ['server_id' => $server->serverId])
                ->with('success', 'Aucun défaut trouvé pour le jeu ' . $server->game . '.');
        }

        $inserted = 0;
        foreach ($defaults as $default) {
            $exists = ServerConfig::where('serverId', $server->serverId)
                ->where('parameter', $default->parameter)
                ->exists();
            if (!$exists) {
                ServerConfig::create([
                    'serverId'  => $server->serverId,
                    'parameter' => $default->parameter,
                    'value'     => $default->value,
                ]);
                $inserted++;
            }
        }

        $msg = $inserted > 0
            ? "{$inserted} paramètre(s) importé(s) depuis les défauts du jeu {$server->game}."
            : 'Tous les paramètres par défaut existent déjà.';

        return redirect()->route('admin.server-config.index', ['server_id' => $server->serverId])
            ->with('success', $msg);
    }
}
