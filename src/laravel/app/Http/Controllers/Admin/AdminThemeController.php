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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class AdminThemeController extends Controller
{
    public function __construct(private ThemeService $themes) {}

    public function index()
    {
        $themes = $this->themes->getAll();
        $active = $this->themes->getActive();
        return view('admin.themes.index', compact('themes', 'active'));
    }

    public function edit(string $slug)
    {
        $theme = $this->themes->load($slug);
        return view('admin.themes.edit', compact('theme', 'slug'));
    }

    public function update(Request $request, string $slug)
    {
        $data = $request->validate([
            'meta.name'         => ['required', 'string', 'max:64'],
            'meta.description'  => ['nullable', 'string', 'max:255'],
            'colors'            => ['required', 'array'],
            // Values land verbatim inside a <style> block, so reject anything
            // that could close it or inject extra rules.
            'colors.*'          => ['string', 'max:128', 'regex:/^[^<>{};@\\\\`]+$/'],
            'typography.*'      => ['string', 'max:128', 'regex:/^[^<>{};@\\\\`]+$/'],
            'layout.*'          => ['string', 'max:128', 'regex:/^[^<>{};@\\\\`]+$/'],
        ]);

        $existing = $this->themes->load($slug);
        $merged   = array_replace_recursive($existing, $data);
        $this->themes->createCustom($slug, $merged);

        return redirect()->route('admin.themes.index')->with('success', 'Theme updated.');
    }

    public function activate(string $slug)
    {
        $this->themes->setActive($slug);
        return back()->with('success', 'Theme activated.');
    }

    public function duplicate(string $slug)
    {
        $newSlug = $slug . '-copy-' . substr(md5(uniqid()), 0, 6);
        $this->themes->duplicate($slug, $newSlug);
        return redirect()->route('admin.themes.edit', $newSlug)->with('success', 'Theme duplicated.');
    }

    public function destroy(string $slug)
    {
        $this->themes->delete($slug);
        return redirect()->route('admin.themes.index')->with('success', 'Theme deleted.');
    }
}
