<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_Bans')) {
            Schema::create('hlstats_Bans', function (Blueprint $table) {
                $table->increments('banId');
                $table->unsignedInteger('playerId')->default(0)->index();
                $table->dateTime('created')->useCurrent();
                $table->dateTime('expires')->nullable()->comment('NULL = permanent');
                $table->enum('type', ['steamid', 'ip', 'name'])->default('steamid');
                $table->unsignedInteger('adminId')->default(0)->index();
                $table->string('reason', 255)->default('');
                $table->string('playerIp', 32)->default('');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hlstats_Bans');
    }
};
