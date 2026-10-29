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

use App\Models\Award;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeAwardsCommand extends Command
{
    protected $signature   = 'hlstats:compute-awards {--date= : Date to compute awards for (YYYY-MM-DD, defaults to today)}';
    protected $description = 'Compute daily awards for all games';

    public function handle(): int
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $from = $date->copy()->startOfDay();
        $to   = $date->copy()->endOfDay();

        $awards = Award::all();

        foreach ($awards as $award) {
            if ($award->awardType === 'W') {
                // Top player with this weapon
                $result = DB::table('hlstats_PlayerWeapons as pw')
                    ->join('hlstats_Weapons as w', 'pw.weaponId', '=', 'w.weaponId')
                    ->join('hlstats_Players as p', 'pw.playerId', '=', 'p.playerId')
                    ->where('w.code', $award->awardCode)
                    ->where('p.game', $award->game)
                    ->select('p.playerId', 'p.lastName', DB::raw('SUM(pw.kills) as total'))
                    ->groupBy('p.playerId', 'p.lastName')
                    ->orderByDesc('total')
                    ->first();
            } else {
                // Top player for an action event
                $result = DB::table('hlstats_Events_Actions as ea')
                    ->join('hlstats_Actions as a', 'ea.actionId', '=', 'a.id')
                    ->join('hlstats_Players as p', 'ea.playerId', '=', 'p.playerId')
                    ->where('a.code', $award->awardCode)
                    ->where('ea.event_time', '>=', $from)
                    ->where('ea.event_time', '<=', $to)
                    ->select('p.playerId', 'p.lastName', DB::raw('COUNT(*) as total'))
                    ->groupBy('p.playerId', 'p.lastName')
                    ->orderByDesc('total')
                    ->first();
            }

            if ($result) {
                $award->d_winner_id    = $result->playerId;
                $award->d_winner_name  = $result->lastName;
                $award->d_winner_count = $result->total;
                $award->save();
                $this->line("Award <info>{$award->name}</info> → {$result->lastName} ({$result->total})");
            }
        }

        $this->info("Awards computed for {$date->toDateString()}.");
        return self::SUCCESS;
    }
}
