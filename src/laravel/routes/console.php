<?php

use App\Console\Commands\CheckServersCommand;
use App\Console\Commands\ComputeAwardsCommand;
use App\Console\Commands\PruneEventsCommand;
use App\Console\Commands\SteamSyncCommand;
use App\Console\Commands\UpdatePlayerGeoIpCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckServersCommand::class)->everyFiveMinutes();
Schedule::command(SteamSyncCommand::class)->hourly();
Schedule::command(UpdatePlayerGeoIpCommand::class)->everyFifteenMinutes()->withoutOverlapping();
Schedule::command(ComputeAwardsCommand::class)->dailyAt('00:05');
Schedule::command(PruneEventsCommand::class, ['--days=90'])->weekly();
Schedule::command('location:update')->monthly();

