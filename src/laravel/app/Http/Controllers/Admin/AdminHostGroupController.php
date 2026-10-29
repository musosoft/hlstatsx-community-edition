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
use App\Models\HostGroup;
use Illuminate\Http\Request;

class AdminHostGroupController extends Controller
{
    public function index()
    {
        $groups = HostGroup::orderBy('name')->get();
        return view('admin.host-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.host-groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'pattern' => ['required', 'string', 'max:255'],
        ]);
        HostGroup::create($data);
        return redirect()->route('admin.host-groups.index')->with('success', 'Host group created.');
    }

    public function edit(int $id)
    {
        $group = HostGroup::findOrFail($id);
        return view('admin.host-groups.edit', compact('group'));
    }

    public function update(Request $request, int $id)
    {
        $group = HostGroup::findOrFail($id);
        $data  = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'pattern' => ['required', 'string', 'max:255'],
        ]);
        $group->update($data);
        return redirect()->route('admin.host-groups.index')->with('success', 'Host group updated.');
    }

    public function destroy(int $id)
    {
        HostGroup::findOrFail($id)->delete();
        return redirect()->route('admin.host-groups.index')->with('success', 'Host group deleted.');
    }
}
