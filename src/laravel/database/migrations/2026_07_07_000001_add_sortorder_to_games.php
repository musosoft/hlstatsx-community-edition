<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 *
 * Add explicit display order for games so ACP and public pages can use a
 * consistent, user-managed ordering.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_Games')) {
            throw new RuntimeException('Cannot add game sort order: hlstats_Games is missing.');
        }

        if (! Schema::hasColumn('hlstats_Games', 'sortorder')) {
            Schema::table('hlstats_Games', function (Blueprint $table) {
                $table->unsignedTinyInteger('sortorder')->default(0)->after('hidden');
            });
        }
    }

    public function down(): void
    {
        Schema::table('hlstats_Games', function (Blueprint $table) {
            $table->dropColumn('sortorder');
        });
    }
};
