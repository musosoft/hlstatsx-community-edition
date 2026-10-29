<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSteamUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || empty($user->steam_id)) {
            return redirect()
                ->route('account.index')
                ->withErrors(['steam' => __('Please sign in with Steam to access your account.')]);
        }

        return $next($request);
    }
}
