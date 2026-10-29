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

class EventKill extends Model
{
    protected $table = 'hlstats_Events_Frags';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'eventTime', 'serverId', 'map', 'killerId', 'victimId',
        'weapon', 'headshot',
        'killerRole', 'victimRole', 'pos_x', 'pos_y', 'pos_z',
        'pos_victim_x', 'pos_victim_y', 'pos_victim_z',
    ];

    public function killer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'killerId', 'playerId');
    }

    public function victim(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'victimId', 'playerId');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'serverId', 'serverId');
    }
}
