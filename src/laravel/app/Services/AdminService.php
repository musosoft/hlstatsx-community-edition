<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 * A modern Laravel 13 rewrite of the HLStatsX:CE web frontend, preserving the original MySQL schema.
 *
 * A long lineage of open-source stats for Half-Life & Source engine games:
 *   HLstats (Simon Garner, 2001) -> HLstatsX (Tobias Oetzel, 2005)
 *   -> HLstatsX:CE (Nicholas Hastings, 2008) -> This rebase (Royal-Multi-Gamers, 2026)
 *
 * Perl daemon sourced from SnipeZilla/HLSTATS-2.
 *
 * Copyright (C) 2025-2026 Royal-Multi-Gamers
 * Licensed under the GNU General Public License v2.0
 * https://www.gnu.org/licenses/gpl-2.0.html
 *
 * https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel
 */

namespace App\Services;

use App\Models\Ban;
use App\Models\Player;
use App\Models\PlayerUniqueId;
use Illuminate\Support\Facades\DB;

class AdminService
{
    /**
     * Ban a player with a reason and optional duration.
     */
    public function banPlayer(int $playerId, string $reason, ?int $days, ?string $ip = null): Ban
    {
        $expires = $days
            ? now()->addDays($days)->toDateTimeString()
            : null; // null = permanent

        $ban = Ban::create([
            'playerId'  => $playerId,
            'created'   => now()->toDateTimeString(),
            'expires'   => $expires,
            'type'      => 'steamid',
            'reason'    => $reason,
            'playerIp'  => $ip ?? '',
        ]);

        // Mark player as banned (hideranking=1)
        Player::where('playerId', $playerId)->update(['hideranking' => 1]);

        return $ban;
    }

    /**
     * Unban a player by ban ID.
     */
    public function unbanPlayer(int $banId): bool
    {
        $ban = Ban::find($banId);
        if (!$ban) {
            return false;
        }

        $ban->delete();

        // Re-enable ranking if no other active bans
        $hasOtherBans = Ban::where('playerId', $ban->playerId)
            ->where('banId', '!=', $banId)
            ->exists();

        if (!$hasOtherBans) {
            Player::where('playerId', $ban->playerId)->update(['hideranking' => 0]);
        }

        return true;
    }

    /**
     * Merge source player profile into target (source gets deleted).
     */
    public function mergeProfiles(int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return false;
        }

        DB::transaction(function () use ($sourceId, $targetId) {
            // Transfer kills/deaths
            DB::table('hlstats_Events_Frags')
                ->where('killerId', $sourceId)
                ->update(['killerId' => $targetId]);

            DB::table('hlstats_Events_Frags')
                ->where('victimId', $sourceId)
                ->update(['victimId' => $targetId]);

            // Transfer uniqueIds
            PlayerUniqueId::where('playerId', $sourceId)
                ->update(['playerId' => $targetId]);

            // Transfer chat/action events
            DB::table('hlstats_Events_Chat')
                ->where('playerId', $sourceId)
                ->update(['playerId' => $targetId]);

            DB::table('hlstats_Events_PlayerActions')
                ->where('playerId', $sourceId)
                ->update(['playerId' => $targetId]);

            DB::table('hlstats_PlayerWeapons')
                ->where('playerId', $sourceId)
                ->update(['playerId' => $targetId]);

            // Delete source player
            Player::where('playerId', $sourceId)->delete();
        });

        return true;
    }

    /**
     * Reset a player's skill to game default.
     */
    public function resetSkill(int $playerId): bool
    {
        $player = Player::find($playerId);
        if (!$player) {
            return false;
        }

        $game = \App\Models\Game::find($player->game);
        $defaultSkill = $game?->defaultSkill ?? 1000;

        $player->update(['skill' => $defaultSkill]);
        return true;
    }
}
