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

namespace App\Console\Commands;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors HLstats_Player.pm::geoLookup/geoUpdate from the legacy Perl daemon.
 * Populates hlstats_Players.{city,state,country,flag,lat,lng} from lastAddress
 * using the local MaxMind GeoLite2-City database.
 */
class UpdatePlayerGeoIpCommand extends Command
{
    protected $signature = 'hlstats:update-geoip
                            {--all      : Re-process every player, even those already geolocated}
                            {--limit=0  : Maximum number of players to process (0 = no limit)}
                            {--chunk=500 : Players per DB chunk}';

    protected $description = 'Resolve player IP addresses to country/city/coordinates via MaxMind';

    public function handle(): int
    {
        $dbPath = config('location.maxmind.local.path', database_path('maxmind/GeoLite2-City.mmdb'));

        if (! is_file($dbPath)) {
            $this->error("MaxMind database not found at: {$dbPath}");
            $this->line('Run: php artisan location:update');
            return self::FAILURE;
        }

        try {
            $reader = new Reader($dbPath);
        } catch (\Throwable $e) {
            $this->error('Unable to open MaxMind database: ' . $e->getMessage());
            return self::FAILURE;
        }

        $query = DB::table('hlstats_Players')
            ->select('playerId', 'lastAddress')
            ->whereNotNull('lastAddress')
            ->where('lastAddress', '!=', '');

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('flag')->orWhere('flag', '');
            });
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $totalAvailable = (clone $query)->reorder()->count();
        $total          = $limit > 0 ? min($limit, $totalAvailable) : $totalAvailable;
        $chunkSize      = max(50, (int) $this->option('chunk'));
        $processed  = 0;
        $updated    = 0;
        $skipped    = 0;
        $notFound   = 0;

        if ($total === 0) {
            $this->info('No players require geolocation update.');
            return self::SUCCESS;
        }
        $this->info("Processing {$total} player(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('playerId')->chunk($chunkSize, function ($players) use ($reader, &$processed, &$updated, &$skipped, &$notFound, $bar) {
            foreach ($players as $p) {
                $processed++;
                $bar->advance();

                $ip = $this->normalizeIp((string) $p->lastAddress);
                if ($ip === null) {
                    $skipped++;
                    continue;
                }

                try {
                    $rec = $reader->city($ip);
                } catch (AddressNotFoundException) {
                    $notFound++;
                    continue;
                } catch (\Throwable) {
                    $skipped++;
                    continue;
                }

                DB::table('hlstats_Players')
                    ->where('playerId', $p->playerId)
                    ->update([
                        'city'    => $rec->city->name ?? '',
                        'state'   => $rec->mostSpecificSubdivision->name ?? '',
                        'country' => $rec->country->name ?? '',
                        'flag'    => $rec->country->isoCode ?? '',
                        'lat'     => $rec->location->latitude,
                        'lng'     => $rec->location->longitude,
                    ]);
                $updated++;
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Processed: {$processed} | Updated: {$updated} | Not found: {$notFound} | Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Strip port suffix and reject obviously invalid addresses.
     * Mirrors the Perl regex normalization.
     */
    private function normalizeIp(string $raw): ?string
    {
        $ip = trim($raw);
        if ($ip === '') {
            return null;
        }

        // [IPv6]:port
        if (preg_match('/^\[(.+?)]:\d+$/', $ip, $m)) {
            $ip = $m[1];
        }
        // IPv4:port (only one colon)
        elseif (substr_count($ip, ':') === 1 && preg_match('/^(.+?):\d+$/', $ip, $m)) {
            $ip = $m[1];
        }

        // Reject loopback / private ranges — MaxMind has no data for them
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        return $ip;
    }
}
