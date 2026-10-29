<?php

namespace App\Http\Middleware;

use App\Services\AdminAccountService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RevalidateAdminAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');
        if ($admin) {
            $canonical = DB::table('hlstats_Users')->useWritePdo()->where('username', $admin->username)->first();
            $role = $canonical ? AdminAccountService::role((int) $canonical->acclevel) : null;
            if ($role === null) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403, 'This account no longer has admin access.');
            }
            // Never trust the persisted shadow role, including for menu rendering.
            $admin->accessLevel = $role;
        }
        return $next($request);
    }
}
