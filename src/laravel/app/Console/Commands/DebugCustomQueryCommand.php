<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 *
 * Diagnostic command for the custom-API max_players feature: prints what
 * the upstream API returns and whether each server matches.
 */

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugCustomQueryCommand extends Command
{
    protected $signature   = 'hlstats:debug-custom-query {game : Game code (e.g. bbrem)}';
    protected $description = 'Diagnose the custom max-players HTTP API for a given game';

    public function handle(): int
    {
        $game = Game::find($this->argument('game'));
        if (! $game) {
            $this->error("Game not found: {$this->argument('game')}");
            return self::FAILURE;
        }

        if (! $game->query_url) {
            $this->error("No query_url configured for game [{$game->code}]");
            return self::FAILURE;
        }

        $this->info("Game     : {$game->code} — {$game->name}");
        $this->info("URL      : {$game->query_url}");
        $this->info("Match on : {$game->query_match_field}");
        $this->info("Max key  : {$game->query_max_players_field}");
        $this->newLine();

        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'hlstatsx-ce-debug'])
            ->get($game->query_url);

        if (! $response->successful()) {
            $this->error("HTTP {$response->status()}");
            return self::FAILURE;
        }

        $data = $response->json();
        if (! is_array($data)) {
            $this->error('Response is not JSON');
            return self::FAILURE;
        }

        if (! array_is_list($data)) {
            foreach ($data as $value) {
                if (is_array($value) && array_is_list($value)) {
                    $data = $value;
                    break;
                }
            }
        }

        $this->info(count($data) . ' entries returned by API');
        $this->newLine();

        // Show first entry keys so the user knows the case-sensitive field names
        if (! empty($data)) {
            $this->line('<comment>Available keys on first entry:</comment>');
            foreach (array_keys((array) $data[0]) as $key) {
                $sample = is_scalar($data[0][$key] ?? null) ? (string) $data[0][$key] : '[non-scalar]';
                $this->line('  ' . str_pad($key, 24) . ' = ' . mb_strimwidth($sample, 0, 60, '…'));
            }
            $this->newLine();
        }

        $servers = Server::where('game', $game->code)->get();
        $this->line("<comment>Matching {$servers->count()} server(s) of game [{$game->code}]:</comment>");

        foreach ($servers as $server) {
            $needle = (string) $server->name;
            $match  = null;
            foreach ($data as $entry) {
                if (is_array($entry) && ($entry[$game->query_match_field] ?? null) === $needle) {
                    $match = $entry;
                    break;
                }
            }

            if ($match) {
                $max = $match[$game->query_max_players_field] ?? '?';
                $this->line("  <info>✔</info> [{$server->serverId}] " . mb_strimwidth($needle, 0, 50, '…') . " → online, max_players = {$max}");
            } else {
                $this->line("  <error>✘</error> [{$server->serverId}] " . mb_strimwidth($needle, 0, 50, '…') . " → offline (no match for this server)");
            }
        }

        return self::SUCCESS;
    }
}
