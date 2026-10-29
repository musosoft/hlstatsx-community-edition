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

namespace App\Services;

use Stevebauman\Location\Facades\Location;

class GeoIPService
{

    private function resolve(string $ip): ?\Stevebauman\Location\Position
    {
        try {
            return Location::get($ip) ?: null;
        } catch (\Exception) {
            return null;
        }
    }

    public function getCountryCode(string $ip): ?string
    {
        return $this->resolve($ip)?->countryCode;
    }

    public function getCountryName(string $ip): ?string
    {
        return $this->resolve($ip)?->countryName;
    }

    public function getCoordinates(string $ip): ?array
    {
        $position = $this->resolve($ip);

        if (!$position || $position->latitude === null || $position->longitude === null) {
            return null;
        }

        return ['lat' => $position->latitude, 'lng' => $position->longitude];
    }

    public function getFlagImagePath(string $countryCode): string
    {
        return '/hlstatsimg/flags/' . strtolower($countryCode) . '.gif';
    }
}
