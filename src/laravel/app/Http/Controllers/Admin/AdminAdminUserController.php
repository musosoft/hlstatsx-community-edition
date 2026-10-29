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
use App\Services\AdminAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdminUserController extends Controller
{
    public function __construct(private AdminAccountService $accounts) {}

    public function index()
    {
        $users = DB::table('hlstats_Users')->orderBy('username')->get();
        return view('admin.admin-users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.admin-users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:16'],
            'password' => AdminAccountService::passwordRules(),
            'acclevel' => ['required', 'integer', 'in:0,80,100'],
        ]);

        $this->accounts->create($data['username'], $data['password'], (int) $data['acclevel']);

        return redirect()->route('admin.admin-users.index')->with('success', 'User created.');
    }

    public function edit(string $username)
    {
        $user = DB::table('hlstats_Users')->where('username', $username)->firstOrFail();
        return view('admin.admin-users.edit', compact('user'));
    }

    public function update(Request $request, string $username)
    {
        $rules = [
            'acclevel' => ['required', 'integer', 'in:0,80,100'],
        ];
        if ($request->filled('password')) {
            $rules['password'] = AdminAccountService::passwordRules();
        }
        $data = $request->validate($rules);

        $this->accounts->update($username, (int) $data['acclevel'], $data['password'] ?? null);

        return redirect()->route('admin.admin-users.index')->with('success', 'User updated.');
    }

    public function destroy(string $username)
    {
        $this->accounts->delete($username);
        return redirect()->route('admin.admin-users.index')->with('success', 'User deleted.');
    }
}
