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

use App\Services\ServerStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $table = 'hlstats_Servers';
    protected $primaryKey = 'serverId';
    public $timestamps = false;

    protected $fillable = [
        'address', 'port', 'name', 'game', 'publicaddress', 'statusurl',
        'act_map', 'act_players', 'max_players', 'map_started',
        'kills', 'rounds', 'suicides', 'headshots', 'ct_wins', 'ts_wins',
        'bombs_planted', 'bombs_defused', 'country', 'city', 'lat', 'lng',
        'sortorder', 'rcon_password', 'last_event',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game', 'code');
    }

    public function getRealgameAttribute(): string
    {
        if ($this->relationLoaded('game') && $this->getRelation('game')) {
            return $this->getRelation('game')->realgame ?? $this->attributes['game'];
        }
        return $this->attributes['game'];
    }

    public function eventKills(): HasMany
    {
        return $this->hasMany(EventKill::class, 'serverId', 'serverId');
    }

    public function scopeOnline(Builder $query): Builder
    {
        // Online if last event was within the last 5 minutes
        return $query->where('last_event', '>=', now()->subMinutes(5)->timestamp);
    }

    public function scopeVisible(Builder $query): Builder
    {
        // hlstats_Servers has no hidden column – all servers are visible
        return $query;
    }

    public function scopeForGame(Builder $query, string $gameCode): Builder
    {
        return $query->where('game', $gameCode);
    }

    public function getFullAddressAttribute(): string
    {
        return !empty($this->publicaddress) ? $this->publicaddress : $this->address . ':' . $this->port;
    }

    /**
     * Effective max_players, transparently overridden by the game's custom
     * HTTP query API when one is configured (see Game.query_url).
     *
     * The Perl daemon resets this column to 0 for servers it cannot RCON
     * (e.g. BattleBit, which speaks HTTP/JSON only) — so for those games
     * we always prefer the value freshly fetched from the upstream API.
     * The API result is itself cached 60s in ServerStatusService, so this
     * accessor is cheap even when iterating large collections.
     */
    public function getMaxPlayersAttribute($value): int
    {
        $raw = (int) $value;
        $apiValue = app(ServerStatusService::class)->fetchCustomMaxPlayers($this);

        if ($apiValue !== null && $apiValue > 0) {
            return $apiValue;
        }

        return $raw;
    }
}
