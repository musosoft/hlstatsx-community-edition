<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the parts of the admin panel that affect the whole installation:
 * admin accounts, destructive tools, global options, themes and the updater.
 *
 * `auth:admin` alone only proves an account exists; it says nothing about the
 * accessLevel or the game an account is scoped to.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (! $admin || ! $admin->isSuperAdmin()) {
            abort(403, __('This section is restricted to super administrators.'));
        }

        return $next($request);
    }
}
