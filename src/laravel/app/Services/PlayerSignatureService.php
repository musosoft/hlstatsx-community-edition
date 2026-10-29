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

use GdImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Renders the forum signature image advertised on the player profile page
 * (legacy URL: /hlstats.php?mode=playersig&player=X).
 *
 * Backgrounds come from the original HLstatsX asset set: 11 variants per game
 * under hlstatsimg/games/{game}/sig/, with hlstatsimg/sig/ as the fallback.
 */
class PlayerSignatureService
{
    public const WIDTH = 400;

    public const HEIGHT = 75;

    /** Number of background variants shipped per game. */
    public const BACKGROUND_COUNT = 11;

    private const FONT = 'hlstatsimg/sig/font/DejaVuSans.ttf';

    /**
     * GD with FreeType is required to draw the signature. Callers should degrade
     * gracefully (503) rather than emit a broken image when this returns false.
     */
    public function isSupported(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagettftext')
            && function_exists('imagecreatefrompng');
    }

    /**
     * Render the signature as raw PNG bytes.
     *
     * @param  int       $playerId
     * @param  int|null  $background  1..BACKGROUND_COUNT, or null to pick one from the player id
     *
     * @throws ModelNotFoundException when the player does not exist
     */
    public function render(int $playerId, ?int $background = null): string
    {
        $data = $this->loadPlayer($playerId);

        $image = $this->createCanvas($data['game'], $background ?? $this->defaultBackground($playerId));

        $this->drawHeader($image, $data);
        $this->drawStats($image, $data);
        $this->drawFooter($image);

        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }

    /**
     * Clamp an arbitrary user-supplied background number into the available range.
     * Returns null when nothing usable was supplied, so the caller can fall back.
     */
    public function normalizeBackground(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return max(1, min(self::BACKGROUND_COUNT, (int) $value));
    }

    // ──────────────────────────────────────────────────────────────────
    // Data
    // ──────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException
     */
    private function loadPlayer(int $playerId): array
    {
        $player = DB::table('hlstats_Players')
            ->where('playerId', $playerId)
            ->first([
                'playerId', 'lastName', 'game', 'skill', 'kills', 'deaths',
                'headshots', 'shots', 'hits', 'flag', 'country', 'clan', 'hideranking',
            ]);

        if (!$player) {
            throw (new ModelNotFoundException())->setModel(\App\Models\Player::class, [$playerId]);
        }

        $ranked = (int) $player->hideranking === 0;

        // Position among ranked players of the same game — mirrors players.index ordering.
        $position = null;
        $total    = null;
        if ($ranked) {
            $position = DB::table('hlstats_Players')
                ->where('game', $player->game)
                ->where('hideranking', 0)
                ->where('skill', '>', (float) $player->skill)
                ->count() + 1;

            $total = DB::table('hlstats_Players')
                ->where('game', $player->game)
                ->where('hideranking', 0)
                ->count();
        }

        $clanTag = null;
        if ((int) $player->clan > 0) {
            $clanTag = DB::table('hlstats_Clans')->where('clanId', $player->clan)->value('tag');
        }

        $kills  = (int) $player->kills;
        $deaths = (int) $player->deaths;
        $shots  = (int) $player->shots;
        $hits   = (int) $player->hits;

        return [
            'name'     => $this->sanitize((string) $player->lastName),
            'game'     => (string) $player->game,
            'flag'     => strtolower((string) ($player->flag ?: '')),
            'clan'     => $clanTag ? $this->sanitize((string) $clanTag) : null,
            'ranked'   => $ranked,
            'position' => $position,
            'total'    => $total,
            'skill'    => (int) round((float) $player->skill),
            'kills'    => $kills,
            'deaths'   => $deaths,
            'kd'       => $deaths > 0 ? round($kills / $deaths, 2) : (float) $kills,
            'accuracy' => $shots > 0 ? round($hits / $shots * 100, 1) : 0.0,
            'hs'       => $kills > 0 ? round((int) $player->headshots / $kills * 100, 1) : 0.0,
        ];
    }

    /**
     * Player names come straight from game servers: strip control characters and
     * anything that is not valid UTF-8, otherwise imagettftext renders garbage.
     */
    private function sanitize(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? $value;

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        $value = trim($value);

        return $value !== '' ? $value : 'unknown';
    }

    private function defaultBackground(int $playerId): int
    {
        return ($playerId % self::BACKGROUND_COUNT) + 1;
    }

    // ──────────────────────────────────────────────────────────────────
    // Drawing
    // ──────────────────────────────────────────────────────────────────

    private function createCanvas(string $game, int $background): GdImage
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Neutral base so the canvas is never transparent if no background loads.
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, imagecolorallocate($image, 18, 20, 24));

        $backdrop = $this->loadBackground($game, $background);
        if ($backdrop) {
            imagecopyresampled(
                $image, $backdrop,
                0, 0, 0, 0,
                self::WIDTH, self::HEIGHT,
                imagesx($backdrop), imagesy($backdrop)
            );
        }

        // Darkening scrim keeps text readable over every background variant —
        // several of the shipped backgrounds are bright enough to swallow white text.
        $scrim = imagecolorallocatealpha($image, 0, 0, 0, 48);
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $scrim);

        // 1px border
        imagerectangle($image, 0, 0, self::WIDTH - 1, self::HEIGHT - 1, imagecolorallocatealpha($image, 255, 255, 255, 100));

        return $image;
    }

    private function loadBackground(string $game, int $background): ?GdImage
    {
        $game = $this->safeGameCode($game);

        $candidates = array_filter([
            $game ? public_path("hlstatsimg/games/{$game}/sig/{$background}.png") : null,
            public_path("hlstatsimg/sig/{$background}.png"),
            public_path('hlstatsimg/sig/1.png'),
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $image = @imagecreatefrompng($path);
                if ($image instanceof GdImage) {
                    return $image;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function drawHeader(GdImage $image, array $data): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 205, 212, 222);

        $x = 6;

        // Game logo
        $logo = $this->loadGameLogo((string) $data['game']);
        if ($logo) {
            imagecopyresampled($image, $logo, $x, 4, 0, 0, 20, 20, imagesx($logo), imagesy($logo));
            $x += 25;
        }

        // Country flag
        $flag = $this->loadFlag((string) $data['flag']);
        if ($flag) {
            imagecopyresampled($image, $flag, $x, 9, 0, 0, 16, 11, imagesx($flag), imagesy($flag));
            $x += 21;
        }

        // Rank badge is right-aligned; reserve its width before truncating the name.
        $rankText  = $data['ranked']
            ? '#' . number_format((int) $data['position'], 0, '.', ' ') . ' / ' . number_format((int) $data['total'], 0, '.', ' ')
            : 'unranked';
        $rankWidth = $this->textWidth(7.5, $rankText);
        $this->text($image, 7.5, self::WIDTH - 7 - $rankWidth, 18, $muted, $rankText);

        $name = $data['clan'] ? $data['clan'] . ' ' . $data['name'] : $data['name'];
        $name = $this->truncate(11, $name, self::WIDTH - $x - $rankWidth - 18);
        $this->text($image, 11, $x, 19, $white, $name);

        // Separator
        imageline($image, 6, 27, self::WIDTH - 7, 27, imagecolorallocatealpha($image, 255, 255, 255, 105));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function drawStats(GdImage $image, array $data): void
    {
        $label = imagecolorallocate($image, 178, 186, 199);
        $value = imagecolorallocate($image, 255, 255, 255);

        $columns = [
            ['SKILL',  number_format((int) $data['skill'], 0, '.', ' ')],
            ['KILLS',  number_format((int) $data['kills'], 0, '.', ' ')],
            ['DEATHS', number_format((int) $data['deaths'], 0, '.', ' ')],
            ['K/D',    number_format((float) $data['kd'], 2, '.', '')],
            ['ACC',    number_format((float) $data['accuracy'], 1, '.', '') . '%'],
            ['HS',     number_format((float) $data['hs'], 1, '.', '') . '%'],
        ];

        $inner = self::WIDTH - 12;
        $step  = $inner / count($columns);

        foreach ($columns as $index => [$caption, $text]) {
            // Centre each label/value pair inside its own column.
            $centre = 6 + ($step * $index) + ($step / 2);

            $this->text($image, 6.5, (int) round($centre - $this->textWidth(6.5, $caption) / 2), 41, $label, $caption);
            $this->text($image, 9.5, (int) round($centre - $this->textWidth(9.5, $text) / 2), 57, $value, $text);
        }
    }

    private function drawFooter(GdImage $image): void
    {
        $site  = (string) config('services.hlstats.site_name', 'HLStatsX: CE');
        $muted = imagecolorallocate($image, 140, 148, 162);

        $site = $this->truncate(6.5, $site, self::WIDTH - 14);
        $this->text($image, 6.5, self::WIDTH - 7 - $this->textWidth(6.5, $site), 70, $muted, $site);
    }

    // ──────────────────────────────────────────────────────────────────
    // Asset + text helpers
    // ──────────────────────────────────────────────────────────────────

    private function loadGameLogo(string $game): ?GdImage
    {
        $game = $this->safeGameCode($game);

        if ($game === '') {
            return null;
        }

        $path = public_path("hlstatsimg/games/{$game}/game.png");

        if (!is_file($path)) {
            return null;
        }

        $image = @imagecreatefrompng($path);

        return $image instanceof GdImage ? $image : null;
    }

    private function loadFlag(string $flag): ?GdImage
    {
        // Flags ship as GIF in the original asset set.
        if ($flag === '' || !preg_match('/^[a-z]{2}$/', $flag)) {
            return null;
        }

        $path = public_path("hlstatsimg/flags/{$flag}.gif");

        if (!is_file($path) || !function_exists('imagecreatefromgif')) {
            return null;
        }

        $image = @imagecreatefromgif($path);

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * Game codes are interpolated into asset paths. They come from the database
     * rather than the request, but keep them to the shape the asset tree uses so
     * a malformed row can never walk out of public/.
     */
    private function safeGameCode(string $game): string
    {
        return preg_match('/^[a-z0-9_-]{1,32}$/i', $game) ? $game : '';
    }

    private function fontPath(): string
    {
        return public_path(self::FONT);
    }

    /**
     * Draw text with a 1px shadow so it stays readable on light backgrounds.
     */
    private function text(GdImage $image, float $size, int $x, int $y, int $color, string $text): void
    {
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 25);

        imagettftext($image, $size, 0, $x + 1, $y + 1, $shadow, $this->fontPath(), $text);
        imagettftext($image, $size, 0, $x, $y, $color, $this->fontPath(), $text);
    }

    private function textWidth(float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $this->fontPath(), $text);

        return $box === false ? 0 : (int) abs($box[2] - $box[0]);
    }

    /**
     * Shorten text with an ellipsis until it fits within $maxWidth pixels.
     */
    private function truncate(float $size, string $text, int $maxWidth): string
    {
        if ($maxWidth <= 0 || $this->textWidth($size, $text) <= $maxWidth) {
            return $text;
        }

        while (mb_strlen($text) > 1 && $this->textWidth($size, $text . '…') > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
        }

        return $text . '…';
    }
}
