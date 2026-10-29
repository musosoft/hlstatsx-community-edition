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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clan extends Model
{
    protected $table = 'hlstats_Clans';
    protected $primaryKey = 'clanId';
    public $timestamps = false;

    protected $fillable = ['tag', 'name', 'homepage', 'game', 'hidden'];

    // Players whose clan FK points to this clan
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'clan', 'clanId');
    }

    public function scopeForGame(Builder $query, string $gameCode): Builder
    {
        return $query->where('game', $gameCode);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('hidden', 0);
    }
}
