<?php

use App\Http\Controllers\Frontend\ActionController;
use App\Http\Controllers\Frontend\AwardController;
use App\Http\Controllers\Frontend\BanController;
use App\Http\Controllers\Frontend\ChartController;
use App\Http\Controllers\Frontend\ChatController;
use App\Http\Controllers\Frontend\ClanController;
use App\Http\Controllers\Frontend\CountryController;
use App\Http\Controllers\Frontend\GameController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LegacyRedirectController;
use App\Http\Controllers\Frontend\LiveFeedController;
use App\Http\Controllers\Frontend\MapController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PlayerController;
use App\Http\Controllers\Frontend\PlayerSignatureController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\ServerController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\Frontend\SteamAuthController;
use App\Http\Controllers\Frontend\RoleController;
use App\Http\Controllers\Frontend\IngameController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\WeaponController;
use App\Http\Controllers\Frontend\VoiceCommController;
use App\Http\Controllers\LocaleController;
use App\Http\Middleware\EnsureSteamUserAuthenticated;
use Illuminate\Support\Facades\Route;

// Installation is provisioned through the legacy updater and deployment
// tooling, never through a web-accessible schema/bootstrap wizard.

// Language switch
Route::post('/language/{locale}', [LocaleController::class, 'switch'])->name('language.switch');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Steam user space
Route::get('/auth/steam', [SteamAuthController::class, 'redirect'])->name('steam.login');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->name('steam.callback');
Route::get('/account', [AccountController::class, 'index'])->name('account.index');
Route::post('/account', [AccountController::class, 'update'])
    ->middleware(EnsureSteamUserAuthenticated::class)
    ->name('account.update');
Route::post('/account/logout', [AccountController::class, 'logout'])
    ->middleware(EnsureSteamUserAuthenticated::class)
    ->name('account.logout');

// Public frontend
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/hlstats.php', [LegacyRedirectController::class, 'redirect'])->name('legacy');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/game/{code}', [GameController::class, 'show'])->name('game.show');

Route::prefix('players')->name('players.')->group(function () {
    Route::get('/', [PlayerController::class, 'index'])->name('index');
    Route::get('/{id}', [PlayerController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/events', [PlayerController::class, 'events'])->name('events')->where('id', '[0-9]+');
    Route::get('/{id}/sessions', [PlayerController::class, 'sessions'])->name('sessions')->where('id', '[0-9]+');
    Route::get('/{id}/awards', [PlayerController::class, 'awards'])->name('awards')->where('id', '[0-9]+');
    Route::get('/{id}/chat', [PlayerController::class, 'chat'])->name('chat')->where('id', '[0-9]+');
    Route::get('/{id}/signature.png', [PlayerSignatureController::class, 'show'])->name('signature')->where('id', '[0-9]+');
});

Route::prefix('clans')->name('clans.')->group(function () {
    Route::get('/', [ClanController::class, 'index'])->name('index');
    Route::get('/{id}', [ClanController::class, 'show'])->name('show')->where('id', '[0-9]+');
});

Route::prefix('servers')->name('servers.')->group(function () {
    Route::get('/', [ServerController::class, 'index'])->name('index');
    Route::get('/{id}', [ServerController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/status', [ServerController::class, 'status'])->name('status')->where('id', '[0-9]+');
});

Route::get('/weapons', [WeaponController::class, 'index'])->name('weapons.index');
Route::get('/weapons/{code}', [WeaponController::class, 'show'])->name('weapons.show');

Route::prefix('maps')->name('maps.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
    Route::get('/markers', [MapController::class, 'markers'])->name('markers');
    Route::get('/{map}', [MapController::class, 'show'])->name('show');
});

Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
Route::get('/countries/clans', [CountryController::class, 'clans'])->name('countries.clans');
Route::get('/countries/clans/{flag}', [CountryController::class, 'clanDetail'])->name('countries.clan-detail');
Route::get('/awards', [AwardController::class, 'index'])->name('awards.index');
Route::get('/awards/{id}/detail', [AwardController::class, 'awardDetail'])->name('awards.detail')->where('id', '[0-9]+');
Route::get('/awards/rank/{id}', [AwardController::class, 'rankDetail'])->name('awards.rank')->where('id', '[0-9]+');
Route::get('/awards/ribbon/{id}', [AwardController::class, 'ribbonDetail'])->name('awards.ribbon')->where('id', '[0-9]+');
Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
Route::get('/roles/{code}', [RoleController::class, 'show'])->name('roles.show');
Route::view('/help', 'frontend.help')->name('help');

// ── Editable static pages (privacy, cookies, legal notice, custom pages) ──
Route::get('/privacy', [PageController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');
Route::get('/cookies', [PageController::class, 'show'])->defaults('slug', 'cookies')->name('cookies');
Route::get('/legal', [PageController::class, 'show'])->defaults('slug', 'legal')->name('legal');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show')->where('slug', '[a-z0-9-]+');
Route::get('/actions', [ActionController::class, 'index'])->name('actions.index');
Route::get('/actions/{id}', [ActionController::class, 'show'])->name('actions.show')->where('id', '[0-9]+');
Route::get('/bans', [BanController::class, 'index'])->name('bans.index');
Route::get('/voicecomm', [VoiceCommController::class, 'index'])->name('voicecomm.index');
Route::get('/voicecomm/discord/{id}', [VoiceCommController::class, 'discord'])->name('voicecomm.discord')->where('id', '[0-9]+');
Route::get('/voicecomm/teamspeak/{id}', [VoiceCommController::class, 'teamspeak'])->name('voicecomm.teamspeak')->where('id', '[0-9]+');
Route::get('/voicecomm/steam/{id}', [VoiceCommController::class, 'steam'])->name('voicecomm.steam')->where('id', '[0-9]+');

// Ingame overlay interface (minimal HTML for in-game MOTD/HUD)
Route::prefix('ingame')->name('ingame.')->group(function () {
    Route::get('/players',  [IngameController::class, 'players'])->name('players');
    Route::get('/clans',    [IngameController::class, 'clans'])->name('clans');
    Route::get('/maps',     [IngameController::class, 'maps'])->name('maps');
    Route::get('/servers',  [IngameController::class, 'servers'])->name('servers');
    Route::get('/weapons',  [IngameController::class, 'weapons'])->name('weapons');
    Route::get('/statsme',  [IngameController::class, 'statsme'])->name('statsme');
    Route::get('/motd',     [IngameController::class, 'motd'])->name('motd');
});

// API
Route::prefix('api/v1')->name('api.')->group(function () {
    Route::get('/live-feed', [LiveFeedController::class, 'index'])->name('live-feed');
    Route::get('/chart/activity/{serverId}', [ChartController::class, 'activity'])->name('chart.activity');
    Route::get('/map/markers', [MapController::class, 'markers'])->name('map.markers');
});

// Admin routes
require __DIR__ . '/admin.php';
