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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEventsCommand extends Command
{
    protected $signature   = 'hlstats:prune-events {--days=90 : Delete events older than this many days}';
    protected $description = 'Delete old events to keep the database size manageable';

    public function handle(): int
    {
        $days      = (int)$this->option('days');
        $threshold = now()->subDays($days)->toDateTimeString();

        $tables = [
            'hlstats_Events_Frags',
            'hlstats_Events_Connects',
            'hlstats_Events_Chat',
            'hlstats_Events_PlayerActions',
        ];

        foreach ($tables as $table) {
            $deleted = DB::table($table)->where('eventTime', '<', $threshold)->delete();
            $this->line("Pruned <info>{$deleted}</info> rows from {$table}");
        }

        $this->info("Event pruning complete (threshold: {$threshold}).");
        return self::SUCCESS;
    }
}
