<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 *
 * Per-game optional HTTP JSON API to fetch the max_players value for servers
 * that do NOT speak A2S_INFO (e.g. BattleBit Remastered, which exposes a
 * public REST endpoint listing all live servers).
 *
 * All columns are nullable: games without these settings keep the current
 * UDP A2S_INFO ping behaviour unchanged.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_Games')) {
            throw new RuntimeException('Cannot add query settings: hlstats_Games is missing.');
        }

        foreach ([
            'query_url' => fn (Blueprint $table) => $table->string('query_url', 255)->nullable()->after('hidden'),
            'query_match_field' => fn (Blueprint $table) => $table->string('query_match_field', 64)->nullable()->after('query_url'),
            'query_max_players_field' => fn (Blueprint $table) => $table->string('query_max_players_field', 64)->nullable()->after('query_match_field'),
        ] as $column => $addColumn) {
            if (! Schema::hasColumn('hlstats_Games', $column)) {
                Schema::table('hlstats_Games', $addColumn);
            }
        }
    }

    public function down(): void
    {
        Schema::table('hlstats_Games', function (Blueprint $table) {
            $table->dropColumn(['query_url', 'query_match_field', 'query_max_players_field']);
        });
    }
};
