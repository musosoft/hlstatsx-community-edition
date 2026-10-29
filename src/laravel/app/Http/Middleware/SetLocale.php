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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = Cache::remember('available_locales', 86400, fn() =>
            collect(glob(lang_path('*.json')))
                ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
                ->all()
        );

        $queryLocale = $request->query('lang');

        if (is_string($queryLocale) && in_array($queryLocale, $allowed, true)) {
            // Locale in URL takes priority and persists in session.
            $locale = $queryLocale;
            session(['locale' => $locale]);
        } elseif (session()->has('locale')) {
            // User made an explicit choice — honour it
            $locale = session('locale');
        } else {
            // Auto-detect from Accept-Language header
            $preferred = $request->getPreferredLanguage($allowed);
            $locale    = $preferred ?? config('app.locale', 'en');
        }

        if (!in_array($locale, $allowed)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
