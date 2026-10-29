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

class Option extends Model
{
    protected $table = 'hlstats_Options';
    protected $primaryKey = 'keyname';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['keyname', 'value'];

    /** In-process cache — populated with a single SELECT * on first access. */
    private static array $_cache = [];
    private static bool  $_loaded = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$_loaded) {
            self::$_cache  = static::all()->pluck('value', 'keyname')->all();
            self::$_loaded = true;
        }

        return self::$_cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['keyname' => $key], ['value' => $value]);
        self::$_cache[$key] = $value;
    }

    /** Force a reload on next get() — useful in tests or after bulk imports. */
    public static function flushCache(): void
    {
        self::$_loaded = false;
        self::$_cache  = [];
    }
}
