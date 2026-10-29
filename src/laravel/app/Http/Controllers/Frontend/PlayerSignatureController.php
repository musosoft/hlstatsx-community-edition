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

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\PlayerSignatureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlayerSignatureController extends Controller
{
    /** Signatures are hotlinked from forums — cache the rendered PNG. */
    private const TTL = 300;

    public function __construct(private readonly PlayerSignatureService $signatures)
    {
    }

    public function show(Request $request, int $id): Response
    {
        if (!$this->signatures->isSupported()) {
            Log::error('Player signatures require the GD extension with FreeType support; it is not loaded.');

            abort(503, 'Signature rendering is unavailable.');
        }

        $background = $this->signatures->normalizeBackground(
            $request->query('background', $request->query('bg'))
        );

        try {
            $png = Cache::remember(
                "player-signature:{$id}:" . ($background ?? 'auto'),
                self::TTL,
                fn (): string => $this->signatures->render($id, $background)
            );
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return response($png, 200, [
            'Content-Type'   => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Cache-Control'  => 'public, max-age=' . self::TTL,
        ]);
    }
}
