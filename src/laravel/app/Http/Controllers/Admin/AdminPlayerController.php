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
use App\Models\Player;
use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminPlayerController extends Controller
{
    public function __construct(private AdminService $admin) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $game   = $request->query('game');

        $players = Player::when($search, fn($q) => $q->where('lastName', 'like', '%' . $search . '%'))
            ->when($game, fn($q) => $q->where('game', $game))
            ->orderByDesc('skill')
            ->paginate(50);

        return view('admin.players.index', compact('players', 'search', 'game'));
    }

    public function edit(int $id)
    {
        $player = Player::findOrFail($id);
        return view('admin.players.edit', compact('player'));
    }

    public function update(Request $request, int $id)
    {
        $player = Player::findOrFail($id);
        $data = $request->validate([
            'lastName' => ['required', 'string', 'max:64'],
            'hideranking' => ['boolean'],
        ]);
        $player->update($data);
        return redirect()->route('admin.players.index')->with('success', 'Player updated.');
    }

    public function destroy(int $id)
    {
        Player::findOrFail($id)->delete();
        return redirect()->route('admin.players.index')->with('success', 'Player deleted.');
    }

    public function resetSkill(int $id)
    {
        $this->admin->resetSkill($id);
        return back()->with('success', 'Skill reset to 1000.');
    }

    public function merge(Request $request)
    {
        $data = $request->validate([
            'source_id' => ['required', 'integer'],
            'target_id' => ['required', 'integer', 'different:source_id'],
        ]);
        $this->admin->mergeProfiles((int)$data['source_id'], (int)$data['target_id']);
        return back()->with('success', 'Profiles merged.');
    }
}
