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

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * An editable static page (privacy policy, cookie policy, legal notice, ...).
 * One row per slug and locale.
 */
class Page extends Model
{
    protected $table = 'hlstats_pages';

    protected $fillable = [
        'slug', 'locale', 'title', 'body',
        'is_published', 'show_in_footer', 'is_system', 'sort_order',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'show_in_footer' => 'boolean',
        'is_system'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    /** Slugs the frontend and the cookie banner depend on — never deletable. */
    public const SYSTEM_SLUGS = ['privacy', 'cookies', 'legal'];

    public const FALLBACK_LOCALE = 'en';

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Resolve a published page for the given locale, falling back to English
     * when the slug has not been translated yet.
     */
    public static function resolve(string $slug, ?string $locale = null): ?self
    {
        $locale = $locale ?? app()->getLocale();

        return static::published()->where('slug', $slug)->where('locale', $locale)->first()
            ?? static::published()->where('slug', $slug)->where('locale', self::FALLBACK_LOCALE)->first()
            ?? static::published()->where('slug', $slug)->first();
    }

    /** Published pages flagged for the footer, in the active locale. */
    public static function footerLinks(?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        return static::published()
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('slug')
            ->map(fn (Collection $translations) => $translations->firstWhere('locale', $locale)
                ?? $translations->firstWhere('locale', self::FALLBACK_LOCALE)
                ?? $translations->first())
            ->sortBy('sort_order')
            ->values();
    }

    /** Replace the installation placeholders used in the shipped default texts. */
    public function renderedBody(): string
    {
        return strtr($this->body, self::placeholders());
    }

    public function renderedTitle(): string
    {
        return strtr($this->title, self::placeholders());
    }

    /** @return array<string, string> */
    private static function placeholders(): array
    {
        return [
            '%SITE_NAME%' => (string) Option::get('sitename', config('services.hlstats.site_name', 'HLStatsX: CE')),
            '%SITE_URL%'  => (string) Option::get('siteurl', config('app.url')),
            '%CONTACT%'   => (string) Option::get('contact', ''),
        ];
    }
}
