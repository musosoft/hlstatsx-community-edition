<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            throw new RuntimeException('Cannot add Steam fields: users table is missing.');
        }

        $hasSteamId = Schema::hasColumn('users', 'steam_id');
        $hasPlayerId = Schema::hasColumn('users', 'player_id');

        Schema::table('users', function (Blueprint $table) use ($hasSteamId, $hasPlayerId) {
            if (!$hasSteamId) {
                $table->string('steam_id', 32)->nullable()->unique()->after('email');
            }

            if (!$hasPlayerId) {
                $table->unsignedInteger('player_id')->nullable()->index()->after('steam_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $hasSteamId = Schema::hasColumn('users', 'steam_id');
        $hasPlayerId = Schema::hasColumn('users', 'player_id');

        Schema::table('users', function (Blueprint $table) use ($hasSteamId, $hasPlayerId) {
            if ($hasPlayerId) {
                $table->dropColumn('player_id');
            }

            if ($hasSteamId) {
                $table->dropUnique('users_steam_id_unique');
                $table->dropColumn('steam_id');
            }
        });
    }
};
