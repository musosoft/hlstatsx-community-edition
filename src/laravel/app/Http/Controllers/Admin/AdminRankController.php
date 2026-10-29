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
use App\Models\Rank;
use Illuminate\Http\Request;

class AdminRankController extends Controller
{
    public function index(Request $request)
    {
        $games = Game::ordered()->get();
        $game  = $request->input('game', $games->first()?->code);
        $ranks = Rank::where('game', $game)->orderBy('minKills')->get();
        return view('admin.ranks.index', compact('games', 'game', 'ranks'));
    }

    public function create(Request $request)
    {
        $games = Game::ordered()->get();
        $selectedGame = $request->input('game', $games->first()?->code);
        return view('admin.ranks.create', compact('games', 'selectedGame'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'game'     => ['required', 'string', 'exists:hlstats_Games,code'],
            'rankName' => ['required', 'string', 'max:50'],
            'image'    => ['required', 'string', 'max:30'],
            'minKills' => ['required', 'integer', 'min:0'],
            'maxKills' => ['required', 'integer'],
        ]);
        Rank::create($data);
        return redirect()->route('admin.ranks.index', ['game' => $data['game']])->with('success', 'Rank created.');
    }

    public function edit(int $id)
    {
        $rank  = Rank::findOrFail($id);
        $games = Game::ordered()->get();
        return view('admin.ranks.edit', compact('rank', 'games'));
    }

    public function update(Request $request, int $id)
    {
        $rank = Rank::findOrFail($id);
        $data = $request->validate([
            'game'     => ['required', 'string', 'exists:hlstats_Games,code'],
            'rankName' => ['required', 'string', 'max:50'],
            'image'    => ['required', 'string', 'max:30'],
            'minKills' => ['required', 'integer', 'min:0'],
            'maxKills' => ['required', 'integer'],
        ]);
        $rank->update($data);
        return redirect()->route('admin.ranks.index', ['game' => $rank->game])->with('success', 'Rank updated.');
    }

    public function destroy(int $id)
    {
        $rank = Rank::findOrFail($id);
        $game = $rank->game;
        $rank->delete();
        return redirect()->route('admin.ranks.index', ['game' => $game])->with('success', 'Rank deleted.');
    }
}

