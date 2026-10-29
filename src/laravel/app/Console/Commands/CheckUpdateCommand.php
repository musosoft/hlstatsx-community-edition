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

use App\Http\Controllers\Admin\AdminUpdateController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckUpdateCommand extends Command
{
    protected $signature = 'hlstats:check-update
                            {--fresh : Bypass the 1-hour cache and force a fresh GitHub API call}
                            {--json  : Output the result as JSON}';

    protected $description = 'Check whether a newer HLStatsX:CE release is available on GitHub';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Cache::forget('admin_update_check');
        }

        $info = AdminUpdateController::fetchVersionInfo();

        if ($this->option('json')) {
            $this->line(json_encode([
                'installed' => $info['installed'],
                'latest'    => $info['latestTag'] ?? null,
                'upToDate'  => $info['upToDate'],
                'url'       => $info['latest']['html_url'] ?? null,
            ], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->line("Installed: <info>{$info['installed']}</info>");

        if ($info['latest'] === null) {
            $this->error('Could not reach GitHub API.');
            if (! empty($info['error'])) {
                $this->line('  ↳ ' . $info['error']);
            }
            return self::FAILURE;
        }

        $this->line("Latest:    <info>{$info['latestTag']}</info>");

        if ($info['upToDate']) {
            $this->info('✔ Up to date.');
            return self::SUCCESS;
        }

        $this->warn("↑ Update available: {$info['latestTag']}");
        if (! empty($info['latest']['html_url'])) {
            $this->line("URL: {$info['latest']['html_url']}");
        }
        return self::SUCCESS;
    }
}
