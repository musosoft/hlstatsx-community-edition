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
use App\Models\Role;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function index(Request $request)
    {
        $games = Game::ordered()->get();
        $game  = $request->input('game', $games->first()?->code);
        $roles = Role::where('game', $game)->orderBy('name')->get();
        return view('admin.roles.index', compact('games', 'game', 'roles'));
    }

    public function create(Request $request)
    {
        $games = Game::ordered()->get();
        $selectedGame = $request->input('game', $games->first()?->code);
        return view('admin.roles.create', compact('games', 'selectedGame'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'game'   => ['required', 'string', 'exists:hlstats_Games,code'],
            'code'   => ['required', 'string', 'max:64'],
            'name'   => ['required', 'string', 'max:64'],
            'hidden' => ['boolean'],
        ]);
        $data['hidden'] = $request->has('hidden') ? '1' : '0';
        Role::create($data);
        return redirect()->route('admin.roles.index', ['game' => $data['game']])->with('success', 'Role created.');
    }

    public function edit(int $id)
    {
        $role  = Role::findOrFail($id);
        $games = Game::ordered()->get();
        return view('admin.roles.edit', compact('role', 'games'));
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $data = $request->validate([
            'game'   => ['required', 'string', 'exists:hlstats_Games,code'],
            'code'   => ['required', 'string', 'max:64'],
            'name'   => ['required', 'string', 'max:64'],
        ]);
        $data['hidden'] = $request->has('hidden') ? '1' : '0';
        $role->update($data);
        return redirect()->route('admin.roles.index', ['game' => $role->game])->with('success', 'Role updated.');
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        $game = $role->game;
        $role->delete();
        return redirect()->route('admin.roles.index', ['game' => $game])->with('success', 'Role deleted.');
    }
}

