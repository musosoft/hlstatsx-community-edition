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

use App\Models\Player;
use App\Services\SteamService;
use Illuminate\Console\Command;

class SteamSyncCommand extends Command
{
    protected $signature   = 'hlstats:steam-sync {--limit=500 : Max players to sync}';
    protected $description = 'Sync Steam avatars and display names for players';

    public function __construct(private SteamService $steam)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit   = (int)$this->option('limit');
        $players = Player::whereNotNull('steamId')
            ->where('steamId', '!=', '')
            ->orderByDesc('skill')
            ->limit($limit)
            ->get();

        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        foreach ($players as $player) {
            try {
                $steam64 = $this->steam->steamId32to64($player->steamId);
                $this->steam->getAvatar($steam64);
                $this->steam->getDisplayName($steam64);
            } catch (\Exception) {
                // Skip players with invalid Steam IDs
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$players->count()} players.");

        return self::SUCCESS;
    }
}
