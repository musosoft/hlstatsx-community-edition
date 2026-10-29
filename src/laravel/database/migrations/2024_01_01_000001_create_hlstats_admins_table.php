<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_Admins')) {
            Schema::create('hlstats_Admins', function (Blueprint $table) {
                $table->increments('adminId');
                $table->string('username', 64)->unique();
                $table->string('password', 255);
                $table->string('game', 32)->default('')->comment('Empty = all games');
                $table->unsignedInteger('serverID')->default(0)->comment('0 = all servers');
                $table->enum('accessLevel', ['superadmin', 'admin', 'moderator'])->default('admin');
                $table->rememberToken();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hlstats_Admins');
    }
};
