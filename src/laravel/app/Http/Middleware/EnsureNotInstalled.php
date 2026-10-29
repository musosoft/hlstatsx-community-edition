<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isInstalled()) {
            abort(404);
        }

        return $next($request);
    }

    /**
     * An instance counts as installed as soon as any of these hold true. The
     * marker file alone is not enough: the documented install procedure is
     * entirely CLI-based and never runs the web wizard, which would otherwise
     * leave these unauthenticated routes reachable forever.
     */
    private function isInstalled(): bool
    {
        if (config('app.installed')) {
            return true;
        }

        if (file_exists(storage_path('framework/installed'))) {
            return true;
        }

        // An existing admin account means the application is already in use.
        try {
            if (Schema::hasTable('hlstats_Admins') && DB::table('hlstats_Admins')->exists()) {
                return true;
            }
        } catch (\Throwable) {
            // No usable database connection yet — genuinely not installed.
        }

        return false;
    }
}
