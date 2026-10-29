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
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Editable static pages: privacy policy, cookie policy, legal notice and any
 * page the administrator adds. One row per slug and locale.
 */
class AdminPageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('sort_order')->orderBy('slug')->orderBy('locale')->get();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $page = new Page([
            'locale'         => config('app.locale', 'en'),
            'is_published'   => true,
            'show_in_footer' => true,
            'sort_order'     => 100,
        ]);

        return view('admin.pages.create', compact('page'));
    }

    public function store(Request $request)
    {
        $page = Page::create($this->validated($request));

        return redirect()->route('admin.pages.edit', $page->id)->with('success', 'Page created.');
    }

    public function edit(int $id)
    {
        $page = Page::findOrFail($id);

        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, int $id)
    {
        $page = Page::findOrFail($id);
        $data = $this->validated($request, $page);

        // A system page keeps its slug: the frontend routes and the cookie
        // banner link to it by slug.
        if ($page->is_system) {
            unset($data['slug']);
            $data['is_published'] = true;
        }

        $page->update($data);

        return redirect()->route('admin.pages.edit', $page->id)->with('success', 'Page updated.');
    }

    public function destroy(int $id)
    {
        $page = Page::findOrFail($id);

        if ($page->is_system) {
            return redirect()->route('admin.pages.index')
                ->withErrors(['page' => 'System pages cannot be deleted.']);
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $locales = collect(glob(lang_path('*.json')))
            ->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
            ->all();

        $data = $request->validate([
            'slug'           => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'locale'         => ['required', 'string', 'max:5', Rule::in($locales)],
            'title'          => ['required', 'string', 'max:191'],
            'body'           => ['required', 'string'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published'   => ['nullable', 'boolean'],
            'show_in_footer' => ['nullable', 'boolean'],
        ]);

        $request->validate([
            'slug' => [
                Rule::unique('hlstats_pages', 'slug')
                    ->where(fn ($query) => $query->where('locale', $request->input('locale')))
                    ->ignore($page?->id),
            ],
        ], [], ['slug' => 'slug']);

        $data['is_published']   = $request->boolean('is_published');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['sort_order']     = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
