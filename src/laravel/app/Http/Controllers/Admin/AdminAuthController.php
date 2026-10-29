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
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function __construct(private AdminAccountService $accounts) {}

    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $request->session()->forget(['admin_migrate_user', 'admin_migrate_proof']);
        $result = $this->accounts->authenticate($credentials['username'], $credentials['password']);
        if (isset($result['admin'])) {
            Auth::guard('admin')->login($result['admin'], $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($result) {
            $request->session()->regenerate();
            $request->session()->put('admin_migrate_user', $result['username']);
            $request->session()->put('admin_migrate_proof', $result['proof']);
            return redirect()->route('admin.migrate-password');
        }

        return back()->withErrors(['username' => 'Identifiants invalides.'])->onlyInput('username');
    }

    public function showMigratePassword(Request $request)
    {
        if (! $request->session()->has('admin_migrate_user')) {
            return redirect()->route('admin.login');
        }

        return view('admin.migrate-password', [
            'username' => $request->session()->get('admin_migrate_user'),
        ]);
    }

    public function migratePassword(Request $request)
    {
        $username = $request->session()->get('admin_migrate_user');

        if (! $username) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'password'              => [...AdminAccountService::passwordRules(), 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $admin = $this->accounts->migratePassword($username, (string) $request->session()->get('admin_migrate_proof', ''), $request->password);
        $request->session()->forget(['admin_migrate_user', 'admin_migrate_proof']);
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
