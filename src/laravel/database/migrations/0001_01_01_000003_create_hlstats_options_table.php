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
 * hlstats_Options is normally created by database/install.sql, which runs
 * before the migrations. This guard recreates it when it is missing — on a
 * test database, for instance — so the later migrations that write options
 * (webapp_version, ...) do not abort. It is a no-op on a real installation.
 *
 * Column definitions mirror database/install.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hlstats_Options')) {
            return;
        }

        Schema::create('hlstats_Options', function (Blueprint $table) {
            $table->string('keyname', 32)->default('')->primary();
            $table->string('value', 128)->default('');
            $table->tinyInteger('opttype')->default(1);

            $table->index('opttype');
        });
    }

    public function down(): void
    {
        // Never drop a legacy table that install.sql owns.
    }
};
