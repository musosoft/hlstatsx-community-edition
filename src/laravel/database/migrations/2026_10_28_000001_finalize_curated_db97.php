<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * Idempotent and retry-safe. Fail-closed unless all expected DB97 tables/columns
     * already exist from preceding incremental migrations. Only advances
     * hlstats_Options.dbversion to 97 and sets webapp_version when every expected
     * object is verified present.
     *
     * MySQL/MariaDB only.
     */
    public function up(): void
    {
        // 1. Guard: require dbversion exactly 96 or 97
        $dbVersion = DB::scalar("SELECT `value` FROM `hlstats_Options` WHERE `keyname` = 'dbversion'");
        if ($dbVersion !== '96' && $dbVersion !== '97') {
            throw new \RuntimeException(
                "Migration aborted: hlstats_Options.dbversion must be 96 or 97 before running this migration. Found: {$dbVersion}"
            );
        }

        // 2. Verify every expected Laravel table exists
        $laravelTables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'migrations',
        ];
        foreach ($laravelTables as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException(
                    "Migration aborted: required Laravel table `{$table}` is missing. Ensure preceding migrations have created it."
                );
            }
        }

        // 3. Verify every expected HLstats table exists
        $hlstatsTables = [
            'hlstats_Admins',
            'hlstats_Bans',
            'hlstats_pages',
        ];
        foreach ($hlstatsTables as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException(
                    "Migration aborted: required HLstats table `{$table}` is missing. Ensure preceding migrations have created it."
                );
            }
        }

        // 4. Verify expected columns on hlstats_Games
        $gamesColumns = ['sortorder', 'query_url', 'query_match_field', 'query_max_players_field'];
        foreach ($gamesColumns as $column) {
            if (! Schema::hasColumn('hlstats_Games', $column)) {
                throw new \RuntimeException(
                    "Migration aborted: required column `hlstats_Games`.`{$column}` is missing. Ensure preceding migrations have created it."
                );
            }
        }

        // 5. Verify expected columns on users (Laravel)
        $usersColumns = ['steam_id', 'player_id'];
        foreach ($usersColumns as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw new \RuntimeException(
                    "Migration aborted: required column `users`.`{$column}` is missing. Ensure preceding migrations have created it."
                );
            }
        }

        // 6. Verify hlstats_Users.password length >= 255
        $passwordCol = DB::selectOne(
            "SELECT CHARACTER_MAXIMUM_LENGTH AS len
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'hlstats_Users'
               AND COLUMN_NAME  = 'password'"
        );
        if ($passwordCol === null || ($passwordCol->len ?? 0) < 255) {
            throw new \RuntimeException(
                "Migration aborted: `hlstats_Users`.`password` must have a maximum length of at least 255 characters."
            );
        }

        // 7. Preserve any installation-specific username, but ensure the
        // canonical table still has an active full administrator. Do not
        // recreate the stock `admin` identity if an operator renamed it.
        // hlstats_Admins is a new Laravel table and is expected to start empty.
        $superAdmins = DB::scalar('SELECT COUNT(*) FROM `hlstats_Users` WHERE `acclevel` >= 100');
        if ((int) $superAdmins === 0) {
            throw new \RuntimeException(
                'Migration aborted: `hlstats_Users` must retain at least one active superadmin.'
            );
        }

        // 8. All guards passed — idempotently set version and webapp version
        DB::statement("UPDATE `hlstats_Options` SET `value` = '97' WHERE `keyname` = 'dbversion'");
        DB::statement("UPDATE `hlstats_Options` SET `value` = '1.13.0-CE' WHERE `keyname` = 'webapp_version'");
    }

    /**
     * Reverse the migration.
     *
     * This migration intentionally refuses to narrow or delete data. Rolling
     * back requires restoring from a pre-migration backup or manually reverting
     * the DDL changes (dropping tables / altering columns) before reverting
     * hlstats_Options.dbversion.
     *
     * @throws \RuntimeException Always
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'Rollback not supported by this migration. ' .
            'Restore from a pre-migration backup or manually revert the DB97 changes, ' .
            'then set hlstats_Options.dbversion back to 96.'
        );
    }
};
