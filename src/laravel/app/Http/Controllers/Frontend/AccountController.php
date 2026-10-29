<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerUniqueId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const STEAM64_BASE = 76561197960265728;

    public function index(Request $request): View
    {
        $user = $request->user();
        $player = null;

        if ($user && !$user->player_id && !empty($user->steam_id)) {
            $linkedPlayerId = $this->resolvePlayerIdFromSteamId((string) $user->steam_id);
            if ($linkedPlayerId !== null) {
                $user->player_id = $linkedPlayerId;
                $user->save();
            }
        }

        if ($user && $user->player_id) {
            $player = Player::query()->find($user->player_id);
        }

        return view('frontend.account.index', [
            'user' => $user,
            'player' => $player,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user || empty($user->steam_id)) {
            return redirect()->route('account.index')->withErrors([
                'steam' => __('No player profile linked to this account.'),
            ]);
        }

        $data = $request->validate([
            'fullName' => ['nullable', 'string', 'max:128', 'regex:/^[^<>]*$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:64'],
            'homepage' => ['nullable', 'url', 'max:64'],
        ]);

        $playerIds = $this->resolvePlayerIdsFromSteamId((string) $user->steam_id);

        if (empty($playerIds) && $user->player_id) {
            $playerIds = [(int) $user->player_id];
        }

        if (empty($playerIds)) {
            return redirect()->route('account.index')->withErrors([
                'steam' => __('No player profile linked to this account.'),
            ]);
        }

        Player::query()
            ->whereIn('playerId', $playerIds)
            ->update([
                'fullName' => $data['fullName'] ?: null,
                'email' => $data['email'] ?: null,
                'homepage' => $data['homepage'] ?: null,
            ]);

        return redirect()->route('account.index')->with('success', __('Profile updated successfully.'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('You have been signed out.'));
    }

    private function resolvePlayerIdFromSteamId(string $steamId64): ?int
    {
        $playerIds = $this->resolvePlayerIdsFromSteamId($steamId64);
        if (empty($playerIds)) {
            return null;
        }

        return (int) Player::query()
            ->whereIn('playerId', $playerIds)
            ->orderByDesc('activity')
            ->orderByDesc('kills')
            ->value('playerId');
    }

    private function resolvePlayerIdsFromSteamId(string $steamId64): array
    {
        if (!ctype_digit($steamId64)) {
            return [];
        }

        $accountId = (int) $steamId64 - self::STEAM64_BASE;
        if ($accountId < 0) {
            return [];
        }

        $y = $accountId % 2;
        $z = intdiv($accountId - $y, 2);

        $candidateIds = array_values(array_unique(array_filter([
            $steamId64,
            'STEAM_0:' . $y . ':' . $z,
            'STEAM_1:' . $y . ':' . $z,
            '[U:1:' . $accountId . ']',
            $y . ':' . $z,
            '0:' . $accountId,
            '1:' . $accountId,
            (string) $accountId,
        ])));

        if (empty($candidateIds)) {
            return [];
        }

        $playerIds = PlayerUniqueId::query()
            ->whereIn('uniqueId', $candidateIds)
            ->pluck('playerId')
            ->unique()
            ->values()
            ->all();

        return array_map('intval', $playerIds);
    }
}
