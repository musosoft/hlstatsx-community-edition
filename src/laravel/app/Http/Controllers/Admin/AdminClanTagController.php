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
use App\Models\ClanTag;
use Illuminate\Http\Request;

class AdminClanTagController extends Controller
{
    public function index()
    {
        $tags = ClanTag::orderBy('pattern')->get();
        return view('admin.clan-tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.clan-tags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pattern'  => ['required', 'string', 'max:64', 'unique:hlstats_ClanTags,pattern'],
            'position' => ['required', 'in:EITHER,START,END'],
        ]);
        ClanTag::create($data);
        return redirect()->route('admin.clan-tags.index')->with('success', 'Clan tag created.');
    }

    public function edit(int $id)
    {
        $tag = ClanTag::findOrFail($id);
        return view('admin.clan-tags.edit', compact('tag'));
    }

    public function update(Request $request, int $id)
    {
        $tag  = ClanTag::findOrFail($id);
        $data = $request->validate([
            'pattern'  => ['required', 'string', 'max:64', 'unique:hlstats_ClanTags,pattern,' . $id],
            'position' => ['required', 'in:EITHER,START,END'],
        ]);
        $tag->update($data);
        return redirect()->route('admin.clan-tags.index')->with('success', 'Clan tag updated.');
    }

    public function destroy(int $id)
    {
        ClanTag::findOrFail($id)->delete();
        return redirect()->route('admin.clan-tags.index')->with('success', 'Clan tag deleted.');
    }
}
