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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guard for hlstats_Games, mirroring database/install.sql. Like the
 * hlstats_Options guard, it only creates the table when install.sql has not
 * run (test databases), so the later ALTER migrations can apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hlstats_Games')) {
            return;
        }

        Schema::create('hlstats_Games', function (Blueprint $table) {
            $table->string('code', 32)->default('')->primary();
            $table->string('name', 128)->default('');
            $table->enum('hidden', ['0', '1'])->default('0');
            $table->string('realgame', 32)->default('hl2mp');
            // sortorder and the query_* columns are added by the later
            // migrations, which run unguarded on top of this table.
        });
    }

    public function down(): void
    {
        // Never drop a legacy table that install.sql owns.
    }
};
