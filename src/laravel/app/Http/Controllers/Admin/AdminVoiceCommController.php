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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVoiceCommController extends Controller
{
    // serverType constants
    const TYPE_TS      = 0;
    const TYPE_STEAM   = 1;
    const TYPE_DISCORD = 2;

    public function index()
    {
        $servers = DB::table('hlstats_Servers_VoiceComm')
            ->orderBy('serverType')
            ->orderBy('name')
            ->paginate(50);

        return view('admin.voicecomm.index', compact('servers'));
    }

    public function create()
    {
        return view('admin.voicecomm.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::table('hlstats_Servers_VoiceComm')->insert($data);
        return redirect()->route('admin.voicecomm.index')->with('success', 'Voice server created.');
    }

    public function edit(int $id)
    {
        $server = DB::table('hlstats_Servers_VoiceComm')->where('serverId', $id)->first();
        abort_if(!$server, 404);
        return view('admin.voicecomm.edit', compact('server'));
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('hlstats_Servers_VoiceComm')->where('serverId', $id)->exists(), 404);
        $data = $this->validated($request);
        DB::table('hlstats_Servers_VoiceComm')->where('serverId', $id)->update($data);
        return redirect()->route('admin.voicecomm.index')->with('success', 'Voice server updated.');
    }

    public function destroy(int $id)
    {
        abort_if(!DB::table('hlstats_Servers_VoiceComm')->where('serverId', $id)->exists(), 404);
        DB::table('hlstats_Servers_VoiceComm')->where('serverId', $id)->delete();
        return redirect()->route('admin.voicecomm.index')->with('success', 'Voice server deleted.');
    }

    private function validated(Request $request): array
    {
        $serverType = (int) $request->input('serverType', self::TYPE_TS);

        $rules = [
            'name'       => ['required', 'string', 'max:128'],
            'serverType' => ['required', 'integer', 'in:0,1,2'],
            'descr'      => ['nullable', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'max:128'],
        ];

        if ($serverType === self::TYPE_DISCORD) {
            // addr = Guild ID for Discord
            $rules['addr']      = ['required', 'string', 'max:128', 'regex:/^\d+$/'];
            $rules['queryPort'] = ['nullable', 'integer'];
            $rules['UDPPort']   = ['nullable', 'integer'];
        } elseif ($serverType === self::TYPE_STEAM) {
            // addr = Steam group custom URL (e.g. "RoyalMultiGamers")
            $rules['addr']      = ['required', 'string', 'max:128', 'regex:/^[\w\-]+$/'];
            $rules['queryPort'] = ['nullable', 'integer'];
            $rules['UDPPort']   = ['nullable', 'integer'];
        } else {
            // addr = IP/hostname for TeamSpeak
            $rules['addr']      = ['required', 'string', 'max:128'];
            $rules['queryPort'] = ['required', 'integer', 'min:1', 'max:65535'];
            $rules['UDPPort']   = ['required', 'integer', 'min:1', 'max:65535'];
        }

        $validated = $request->validate($rules);

        // Fill defaults for Discord / Steam (query/udp ports unused)
        if (in_array($serverType, [self::TYPE_DISCORD, self::TYPE_STEAM], true)) {
            $validated['queryPort'] = $validated['queryPort'] ?? 0;
            $validated['UDPPort']   = $validated['UDPPort']   ?? 0;
        }

        return $validated;
    }
}
