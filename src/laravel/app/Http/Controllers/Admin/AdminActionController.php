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
use App\Models\Action;
use App\Models\Game;
use Illuminate\Http\Request;

class AdminActionController extends Controller
{
    public function index(Request $request)
    {
        $games   = Game::ordered()->get();
        $game    = $request->input('game', $games->first()?->code);
        $actions = Action::where('game', $game)->orderBy('code')->get();
        return view('admin.actions.index', compact('games', 'game', 'actions'));
    }

    public function create(Request $request)
    {
        $games = Game::ordered()->get();
        $selectedGame = $request->input('game', $games->first()?->code);
        return view('admin.actions.create', compact('games', 'selectedGame'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'game'                     => ['required', 'string', 'exists:hlstats_Games,code'],
            'code'                     => ['required', 'string', 'max:64'],
            'description'              => ['nullable', 'string', 'max:128'],
            'reward_player'            => ['required', 'integer'],
            'reward_team'              => ['required', 'integer'],
            'team'                     => ['nullable', 'string', 'max:64'],
            'for_PlayerActions'        => ['required', 'in:0,1'],
            'for_PlayerPlayerActions'  => ['required', 'in:0,1'],
            'for_TeamActions'          => ['required', 'in:0,1'],
            'for_WorldActions'         => ['required', 'in:0,1'],
        ]);
        Action::create($data);
        return redirect()->route('admin.actions.index', ['game' => $data['game']])->with('success', 'Action created.');
    }

    public function edit(int $id)
    {
        $action = Action::findOrFail($id);
        $games  = Game::ordered()->get();
        return view('admin.actions.edit', compact('action', 'games'));
    }

    public function update(Request $request, int $id)
    {
        $action = Action::findOrFail($id);
        $data = $request->validate([
            'game'                     => ['required', 'string', 'exists:hlstats_Games,code'],
            'code'                     => ['required', 'string', 'max:64'],
            'description'              => ['nullable', 'string', 'max:128'],
            'reward_player'            => ['required', 'integer'],
            'reward_team'              => ['required', 'integer'],
            'team'                     => ['nullable', 'string', 'max:64'],
            'for_PlayerActions'        => ['required', 'in:0,1'],
            'for_PlayerPlayerActions'  => ['required', 'in:0,1'],
            'for_TeamActions'          => ['required', 'in:0,1'],
            'for_WorldActions'         => ['required', 'in:0,1'],
        ]);
        $action->update($data);
        return redirect()->route('admin.actions.index', ['game' => $action->game])->with('success', 'Action updated.');
    }

    public function destroy(int $id)
    {
        $action = Action::findOrFail($id);
        $game   = $action->game;
        $action->delete();
        return redirect()->route('admin.actions.index', ['game' => $game])->with('success', 'Action deleted.');
    }
}

