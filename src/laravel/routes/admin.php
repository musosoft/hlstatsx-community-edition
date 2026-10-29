<?php

use App\Http\Controllers\Admin\AdminActionController;
use App\Http\Controllers\Admin\AdminAdminUserController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAwardController;
use App\Http\Controllers\Admin\AdminBanController;
use App\Http\Controllers\Admin\AdminClanController;
use App\Http\Controllers\Admin\AdminClanTagController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGameController;
use App\Http\Controllers\Admin\AdminHostGroupController;
use App\Http\Controllers\Admin\AdminOptionsController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminRankController;
use App\Http\Controllers\Admin\AdminRibbonController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminServerConfigController;
use App\Http\Controllers\Admin\AdminServerController;
use App\Http\Controllers\Admin\AdminTeamController;
use App\Http\Controllers\Admin\AdminThemeController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\Admin\AdminVoiceCommController;
use App\Http\Controllers\Admin\AdminWeaponController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Auth
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // Guest routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
        Route::get('/migrate-password', [AdminAuthController::class, 'showMigratePassword'])->name('migrate-password');
        Route::post('/migrate-password', [AdminAuthController::class, 'migratePassword'])->name('migrate-password.submit');
    });

    // Authenticated admin routes
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Players
        Route::prefix('players')->name('players.')->group(function () {
            Route::get('/', [AdminPlayerController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [AdminPlayerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminPlayerController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminPlayerController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/reset-skill', [AdminPlayerController::class, 'resetSkill'])->name('reset-skill');
            Route::post('/merge', [AdminPlayerController::class, 'merge'])->name('merge');
        });

        // Clans
        Route::prefix('clans')->name('clans.')->group(function () {
            Route::get('/', [AdminClanController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [AdminClanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminClanController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminClanController::class, 'destroy'])->name('destroy');
        });

        // Servers
        Route::prefix('servers')->name('servers.')->group(function () {
            Route::get('/', [AdminServerController::class, 'index'])->name('index');
            Route::get('/create', [AdminServerController::class, 'create'])->name('create');
            Route::post('/', [AdminServerController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminServerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminServerController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminServerController::class, 'destroy'])->name('destroy');
        });

        // Games
        Route::prefix('games')->name('games.')->group(function () {
            Route::get('/', [AdminGameController::class, 'index'])->name('index');
            Route::get('/create', [AdminGameController::class, 'create'])->name('create');
            Route::post('/', [AdminGameController::class, 'store'])->name('store');
            Route::get('/{code}/edit', [AdminGameController::class, 'edit'])->name('edit');
            Route::put('/{code}', [AdminGameController::class, 'update'])->name('update');
            Route::delete('/{code}', [AdminGameController::class, 'destroy'])->name('destroy');
            Route::get('/{code}/duplicate', [AdminGameController::class, 'showDuplicate'])->name('duplicate');
            Route::post('/{code}/duplicate', [AdminGameController::class, 'duplicate'])->name('duplicate.store');
        });

        // Weapons
        Route::prefix('weapons')->name('weapons.')->group(function () {
            Route::get('/', [AdminWeaponController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [AdminWeaponController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminWeaponController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminWeaponController::class, 'destroy'])->name('destroy');
        });

        // Bans
        Route::prefix('bans')->name('bans.')->group(function () {
            Route::get('/', [AdminBanController::class, 'index'])->name('index');
            Route::post('/', [AdminBanController::class, 'store'])->name('store');
            Route::delete('/{id}', [AdminBanController::class, 'destroy'])->name('destroy');
        });

        // Voice Servers (TeamSpeak 3, Discord)
        Route::prefix('voicecomm')->name('voicecomm.')->group(function () {
            Route::get('/', [AdminVoiceCommController::class, 'index'])->name('index');
            Route::get('/create', [AdminVoiceCommController::class, 'create'])->name('create');
            Route::post('/', [AdminVoiceCommController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminVoiceCommController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
            Route::put('/{id}', [AdminVoiceCommController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('/{id}', [AdminVoiceCommController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        });

        /*
        |----------------------------------------------------------------------
        | Installation-wide sections — super administrators only.
        |----------------------------------------------------------------------
        | These grant control over accounts, global configuration, the theme
        | injected on every page, destructive data tools and the updater, so
        | they must not be reachable by a game-scoped admin or a moderator.
        */
        Route::middleware('admin.superadmin')->group(function () {

            // Themes
            Route::prefix('themes')->name('themes.')->group(function () {
                Route::get('/', [AdminThemeController::class, 'index'])->name('index');
                Route::get('/{slug}/edit', [AdminThemeController::class, 'edit'])->name('edit');
                Route::put('/{slug}', [AdminThemeController::class, 'update'])->name('update');
                Route::post('/{slug}/activate', [AdminThemeController::class, 'activate'])->name('activate');
                Route::post('/{slug}/duplicate', [AdminThemeController::class, 'duplicate'])->name('duplicate');
                Route::delete('/{slug}', [AdminThemeController::class, 'destroy'])->name('destroy');
            });

            // Options
            Route::get('/options', [AdminOptionsController::class, 'index'])->name('options.index');
            Route::put('/options', [AdminOptionsController::class, 'update'])->name('options.update');

            // Static pages (privacy policy, cookie policy, legal notice, ...)
            Route::prefix('pages')->name('pages.')->group(function () {
                Route::get('/', [AdminPageController::class, 'index'])->name('index');
                Route::get('/create', [AdminPageController::class, 'create'])->name('create');
                Route::post('/', [AdminPageController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [AdminPageController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
                Route::put('/{id}', [AdminPageController::class, 'update'])->name('update')->where('id', '[0-9]+');
                Route::delete('/{id}', [AdminPageController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
            });

            // Admin Users
            Route::prefix('admin-users')->name('admin-users.')->group(function () {
                Route::get('/', [AdminAdminUserController::class, 'index'])->name('index');
                Route::get('/create', [AdminAdminUserController::class, 'create'])->name('create');
                Route::post('/', [AdminAdminUserController::class, 'store'])->name('store');
                Route::get('/{username}/edit', [AdminAdminUserController::class, 'edit'])->name('edit');
                Route::put('/{username}', [AdminAdminUserController::class, 'update'])->name('update');
                Route::delete('/{username}', [AdminAdminUserController::class, 'destroy'])->name('destroy');
            });
        });

        // Ranks
        Route::prefix('ranks')->name('ranks.')->group(function () {
            Route::get('/', [AdminRankController::class, 'index'])->name('index');
            Route::get('/create', [AdminRankController::class, 'create'])->name('create');
            Route::post('/', [AdminRankController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminRankController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminRankController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminRankController::class, 'destroy'])->name('destroy');
        });

        // Teams
        Route::prefix('teams')->name('teams.')->group(function () {
            Route::get('/', [AdminTeamController::class, 'index'])->name('index');
            Route::get('/create', [AdminTeamController::class, 'create'])->name('create');
            Route::post('/', [AdminTeamController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminTeamController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminTeamController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminTeamController::class, 'destroy'])->name('destroy');
        });

        // Roles
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [AdminRoleController::class, 'index'])->name('index');
            Route::get('/create', [AdminRoleController::class, 'create'])->name('create');
            Route::post('/', [AdminRoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminRoleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminRoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->name('destroy');
        });

        // Actions
        Route::prefix('actions')->name('actions.')->group(function () {
            Route::get('/', [AdminActionController::class, 'index'])->name('index');
            Route::get('/create', [AdminActionController::class, 'create'])->name('create');
            Route::post('/', [AdminActionController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminActionController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminActionController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminActionController::class, 'destroy'])->name('destroy');
        });

        // Awards
        Route::prefix('awards')->name('awards.')->group(function () {
            Route::get('/', [AdminAwardController::class, 'index'])->name('index');
            Route::get('/create', [AdminAwardController::class, 'create'])->name('create');
            Route::post('/', [AdminAwardController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminAwardController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminAwardController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminAwardController::class, 'destroy'])->name('destroy');
        });

        // Ribbons
        Route::prefix('ribbons')->name('ribbons.')->group(function () {
            Route::get('/', [AdminRibbonController::class, 'index'])->name('index');
            Route::get('/create', [AdminRibbonController::class, 'create'])->name('create');
            Route::post('/', [AdminRibbonController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminRibbonController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminRibbonController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminRibbonController::class, 'destroy'])->name('destroy');
        });

        // Clan Tags
        Route::prefix('clan-tags')->name('clan-tags.')->group(function () {
            Route::get('/', [AdminClanTagController::class, 'index'])->name('index');
            Route::get('/create', [AdminClanTagController::class, 'create'])->name('create');
            Route::post('/', [AdminClanTagController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminClanTagController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminClanTagController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminClanTagController::class, 'destroy'])->name('destroy');
        });

        // Host Groups
        Route::prefix('host-groups')->name('host-groups.')->group(function () {
            Route::get('/', [AdminHostGroupController::class, 'index'])->name('index');
            Route::get('/create', [AdminHostGroupController::class, 'create'])->name('create');
            Route::post('/', [AdminHostGroupController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminHostGroupController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminHostGroupController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminHostGroupController::class, 'destroy'])->name('destroy');
        });

        // Server Config
        Route::prefix('server-config')->name('server-config.')->group(function () {
            Route::get('/', [AdminServerConfigController::class, 'index'])->name('index');
            Route::post('/', [AdminServerConfigController::class, 'store'])->name('store');
            Route::post('/populate-defaults', [AdminServerConfigController::class, 'populateFromDefaults'])->name('populate-defaults');
            Route::put('/{id}', [AdminServerConfigController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminServerConfigController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('admin.superadmin')->group(function () {

            // Tools
            Route::prefix('tools')->name('tools.')->group(function () {
                Route::get('/', [AdminToolsController::class, 'index'])->name('index');
                Route::post('/optimize-db', [AdminToolsController::class, 'optimizeDb'])->name('optimize-db');
                Route::post('/reset-game', [AdminToolsController::class, 'resetGame'])->name('reset-game');
                Route::post('/delete-players', [AdminToolsController::class, 'deletePlayers'])->name('delete-players');
                Route::post('/partial-reset', [AdminToolsController::class, 'partialReset'])->name('partial-reset');
                Route::post('/reset-collations', [AdminToolsController::class, 'resetCollations'])->name('reset-collations');
            });

            // Source updates are deployed from the curated branch. The upstream
            // in-app updater runs artisan migrations and is intentionally absent.
        });
    });
});
