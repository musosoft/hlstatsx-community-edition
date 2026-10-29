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
use Illuminate\Support\Facades\Schema;

class Game extends Model
{
    protected $table = 'hlstats_Games';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'realgame', 'hidden', 'website',
        'sortorder',
        'defaultSkill', 'killSkillBonus', 'deathSkillPenalty',
        'suicidePenalty', 'teamKillPenalty', 'minPlayers', 'headShotBonus',
        'query_url', 'query_match_field', 'query_max_players_field',
    ];

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'game', 'code');
    }

    public function weapons(): HasMany
    {
        return $this->hasMany(Weapon::class, 'game', 'code');
    }

    public function maps(): HasMany
    {
        return $this->hasMany(GameMap::class, 'game', 'code');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'game', 'code');
    }

    public function scopeVisible(Builder $query): Builder
    {
        // hidden is ENUM('0','1'); use string comparison
        return $query->where('hidden', '0');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        static $hasSortOrder = null;

        if ($hasSortOrder === null) {
            $hasSortOrder = Schema::hasColumn($this->getTable(), 'sortorder');
        }

        if ($hasSortOrder) {
            $query->orderBy('sortorder');
        }

        return $query
            ->orderBy('realgame')
            ->orderBy('name');
    }
}
