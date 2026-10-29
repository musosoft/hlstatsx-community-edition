<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `hlstats_Users`.`password` was varchar(32) — exactly one MD5 digest wide, so
 * the column could not physically hold a bcrypt hash. Widen it so new accounts
 * can be stored with a modern hash while preserving all legacy identities.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_Users')) {
            return;
        }

        $column = DB::selectOne("SHOW FULL COLUMNS FROM `hlstats_Users` LIKE 'password'");
        if (! $column) {
            throw new RuntimeException('Cannot widen legacy passwords: hlstats_Users.password is missing.');
        }

        if (preg_match('/^varchar\((\d+)\)$/i', (string) $column->Type, $matches) === 1 && (int) $matches[1] < 255) {
            DB::statement('ALTER TABLE `hlstats_Users` MODIFY `password` VARCHAR(255) NOT NULL DEFAULT \'\'');
        }

        // Curated Edition deliberately preserves every existing admin identity.
    }

    public function down(): void
    {
        // Not reversed: narrowing the column back would truncate bcrypt hashes.
    }
};
