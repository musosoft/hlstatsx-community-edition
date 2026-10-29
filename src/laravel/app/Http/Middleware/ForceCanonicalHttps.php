<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class ForceCanonicalHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            // Some TLS-terminating proxies connect to the origin over HTTP and
            // do not forward the visitor's original scheme.
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
