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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Award extends Model
{
    protected $table = 'hlstats_Awards';
    protected $primaryKey = 'awardId';
    public $timestamps = false;

    protected $fillable = ['awardType', 'game', 'code', 'name', 'verb', 'd_winner_id', 'winner_id', 'w_winner_count', 'd_winner_count'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game', 'code');
    }

    public function weeklyWinner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'w_winner_id', 'playerId');
    }

    public function dailyWinner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'd_winner_id', 'playerId');
    }

    public function globalWinner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'g_winner_id', 'playerId');
    }
}
