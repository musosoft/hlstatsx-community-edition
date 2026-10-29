<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerUniqueId;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SteamAuthController extends Controller
{
    private const STEAM_OPENID_URL = 'https://steamcommunity.com/openid/login';
    private const STEAM64_BASE = '76561197960265728';

    public function redirect(Request $request): RedirectResponse
    {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => route('steam.callback'),
            'openid.realm' => url('/'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return redirect()->away(self::STEAM_OPENID_URL . '?' . http_build_query($params));
    }

    public function callback(Request $request): RedirectResponse
    {
        $claimedId = (string) $request->query('openid_claimed_id', '');

        if (!$this->isValidOpenIdResponse($request, $claimedId)) {
            return redirect()->route('account.index')
                ->withErrors(['steam' => __('Steam authentication failed. Please try again.')]);
        }

        if (!preg_match('#^https?://steamcommunity\.com/openid/id/(\d+)$#', $claimedId, $matches)) {
            return redirect()->route('account.index')
                ->withErrors(['steam' => __('Invalid Steam identifier received.')]);
        }

        $steamId64 = $matches[1];
        $player = $this->resolvePlayerFromSteamId($steamId64);

        $user = User::firstOrCreate(
            ['steam_id' => $steamId64],
            [
                'name' => $player?->lastName ?: ('Steam ' . substr($steamId64, -6)),
                'email' => $steamId64 . '@steam.local',
                'password' => Str::password(32),
                'player_id' => $player?->playerId,
            ]
        );

        $user->player_id = $player?->playerId;
        if (empty($user->name)) {
            $user->name = $player?->lastName ?: ('Steam ' . substr($steamId64, -6));
        }
        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('success', __('Successfully signed in with Steam.'));
    }

    private function isValidOpenIdResponse(Request $request, string $claimedId): bool
    {
        if ($request->query('openid_mode') !== 'id_res' || $claimedId === '') {
            return false;
        }

        $verification = [];
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'openid_')) {
                $verification[str_replace('openid_', 'openid.', $key)] = $value;
            }
        }

        $verification['openid.mode'] = 'check_authentication';

        try {
            $response = Http::asForm()
                ->timeout(6)
                ->post(self::STEAM_OPENID_URL, $verification);
        } catch (\Throwable) {
            return false;
        }

        if (!$response->successful()) {
            return false;
        }

        $body = (string) $response->body();

        return str_contains($body, "is_valid:true");
    }

    private function resolvePlayerFromSteamId(string $steamId64): ?Player
    {
        $steam2 = $this->steam64ToSteam2($steamId64);
        $steam1 = $this->steam64ToSteam2($steamId64, 1);
        $steam3 = $this->steam64ToSteam3($steamId64);
        $steam2Short = $this->steam64ToSteam2Short($steamId64);
        $accountId = $this->steam64ToAccountId($steamId64);
        $short0 = $accountId !== null ? '0:' . $accountId : null;
        $short1 = $accountId !== null ? '1:' . $accountId : null;

        $candidateIds = array_values(array_filter([
            $steamId64,
            $steam2,
            $steam1,
            $steam3,
            $steam2Short,
            $short0,
            $short1,
            $accountId !== null ? (string) $accountId : null,
        ]));

        if (empty($candidateIds)) {
            return null;
        }

        $playerIds = PlayerUniqueId::query()
            ->whereIn('uniqueId', $candidateIds)
            ->pluck('playerId')
            ->unique()
            ->values();

        if ($playerIds->isEmpty()) {
            return null;
        }

        return Player::query()
            ->whereIn('playerId', $playerIds)
            ->orderByDesc('activity')
            ->orderByDesc('kills')
            ->first();
    }

    private function steam64ToSteam2(string $steamId64, int $universe = 0): ?string
    {
        if (!ctype_digit($steamId64)) {
            return null;
        }

        $accountId = (int) $steamId64 - (int) self::STEAM64_BASE;
        if ($accountId < 0) {
            return null;
        }

        $y = $accountId % 2;
        $z = intdiv($accountId - $y, 2);

        return "STEAM_{$universe}:$y:$z";
    }

    private function steam64ToSteam3(string $steamId64): ?string
    {
        if (!ctype_digit($steamId64)) {
            return null;
        }

        $accountId = (int) $steamId64 - (int) self::STEAM64_BASE;
        if ($accountId < 0) {
            return null;
        }

        return "[U:1:$accountId]";
    }

    private function steam64ToSteam2Short(string $steamId64): ?string
    {
        if (!ctype_digit($steamId64)) {
            return null;
        }

        $accountId = (int) $steamId64 - (int) self::STEAM64_BASE;
        if ($accountId < 0) {
            return null;
        }

        $y = $accountId % 2;
        $z = intdiv($accountId - $y, 2);

        return $y . ':' . $z;
    }

    private function steam64ToAccountId(string $steamId64): ?int
    {
        if (!ctype_digit($steamId64)) {
            return null;
        }

        $accountId = (int) $steamId64 - (int) self::STEAM64_BASE;

        return $accountId >= 0 ? $accountId : null;
    }
}
