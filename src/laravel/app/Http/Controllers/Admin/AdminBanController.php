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
use App\Models\Ban;
use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminBanController extends Controller
{
    public function __construct(private AdminService $admin) {}

    public function index()
    {
        $bans = Ban::with('player')->orderByDesc('created')->paginate(50);
        return view('admin.bans.index', compact('bans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'player_id' => ['required', 'integer'],
            'reason'    => ['nullable', 'string', 'max:255'],
            'days'      => ['nullable', 'integer', 'min:1'],
        ]);

        $this->admin->banPlayer(
            (int)$data['player_id'],
            $data['reason'] ?? '',
            $data['days'] ?? null,
        );

        return back()->with('success', 'Player banned.');
    }

    public function destroy(int $id)
    {
        $this->admin->unbanPlayer($id);
        return back()->with('success', 'Ban removed.');
    }
}
