<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep Laravel off legacy databases until the curated legacy updater completes.
 * This is deliberately a request-time gate: it never attempts to mutate schema.
 */
class EnsureCuratedSchemaReady
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! Schema::hasTable('hlstats_Options')) {
                abort(503, 'The database is not ready for the Laravel frontend.');
            }

            $version = DB::table('hlstats_Options')
                ->where('keyname', 'dbversion')
                ->value('value');
        } catch (\Throwable) {
            abort(503, 'The database is not ready for the Laravel frontend.');
        }

        if (! is_numeric($version) || (int) $version < (int) config('hlstats.required_db_version')) {
            abort(503, 'Run the legacy web updater before enabling the Laravel frontend.');
        }

        return $next($request);
    }
}
