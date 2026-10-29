<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The legacy Perl daemon overwrites the `version` key in hlstats_Options
 * at every startup with its own hardcoded value. Laravel tracks the
 * webapp version under a dedicated `webapp_version` key instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('hlstats_Options')->insertOrIgnore([
            'keyname' => 'webapp_version',
            'value' => '1.0.0-beta2',
            'opttype' => 1,
        ]);
    }

    public function down(): void {}
};
