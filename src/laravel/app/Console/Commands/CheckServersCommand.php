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

use App\Models\Server;
use App\Services\ServerStatusService;
use Illuminate\Console\Command;

class CheckServersCommand extends Command
{
    protected $signature   = 'hlstats:check-servers';
    protected $description = 'Ping all visible servers and update their online status';

    public function __construct(private ServerStatusService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $servers = Server::visible()->get();
        $online  = 0;

        foreach ($servers as $server) {
            // Games with a custom HTTP status API (e.g. BattleBit) don't
            // speak A2S_INFO, so prefer their live-server list when configured.
            $status = $this->service->isOnlineViaCustomApi($server)
                ?? $this->service->ping($server->address, (int) $server->port);
            $updates = [];

            if ($status) {
                $online++;
                $updates['last_event'] = now()->timestamp;
            }

            // Optional: refresh max_players from the game's custom HTTP API.
            // Only overwrite when the API returned a strictly positive value,
            // so a transient 0 (server down, missing field) cannot clobber a
            // previously-known good capacity.
            $max = $this->service->fetchCustomMaxPlayers($server);
            if ($max !== null && $max > 0 && (int) $server->max_players !== $max) {
                $updates['max_players'] = $max;
            }

            if (! empty($updates)) {
                $server->update($updates);
            }

            $this->line(sprintf(
                '%s:%d — %s%s',
                $server->address,
                $server->port,
                $status ? '<info>online</info>' : '<comment>offline</comment>',
                isset($updates['max_players']) ? " (max_players={$updates['max_players']})" : ''
            ));
        }

        $this->info("Done. {$online}/{$servers->count()} servers online.");
        return self::SUCCESS;
    }
}
