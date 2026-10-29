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

use App\Models\Game;
use App\Models\Option;
use App\Models\Player;
use App\Models\Server;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeService
{
    private string $builtinPath;
    private string $customPath;

    public function __construct()
    {
        $this->builtinPath = resource_path('themes');
        $this->customPath = storage_path('app/themes');
    }

    /**
     * Get the currently active theme data.
     */
    public function getActive(): array
    {
        return Cache::remember('theme.active', 3600, function () {
            $slug = Option::get('theme_active', 'hlstatsx-dark');
            return $this->load($slug) ?? $this->load('hlstatsx-dark');
        });
    }

    /**
     * Generate CSS custom properties string for injection into <head>.
     */
    /** Custom property names: letters, digits and dashes only. */
    private const CSS_KEY_PATTERN = '/^[A-Za-z0-9\-]+$/';

    /**
     * Declaration values. Rejects the characters needed to escape a single
     * declaration: < and > (closing the <style> block), { } ; (injecting extra
     * rules), @ (@import) and \ ` (escape tricks). Quotes and commas stay
     * allowed so font stacks such as "'Inter', sans-serif" keep working.
     */
    private const CSS_VALUE_PATTERN = '/^[^<>{};@\\\\`]+$/';

    public function getCssVariables(array $theme): string
    {
        $lines = [];

        foreach (['colors', 'typography', 'layout'] as $section) {
            foreach ($theme[$section] ?? [] as $key => $value) {
                if (! is_scalar($value)) {
                    continue;
                }

                $key   = (string) $key;
                $value = trim((string) $value);

                if (! preg_match(self::CSS_KEY_PATTERN, $key)) {
                    continue;
                }
                if (! preg_match(self::CSS_VALUE_PATTERN, $value)) {
                    continue;
                }

                $lines[] = "  --{$key}: {$value};";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * List all available themes (builtin + custom).
     */
    public function getAll(): Collection
    {
        $themes = collect();

        foreach ([$this->builtinPath, $this->customPath] as $basePath) {
            if (!File::isDirectory($basePath)) {
                continue;
            }

            foreach (File::directories($basePath) as $dir) {
                $jsonPath = $dir . '/theme.json';
                if (File::exists($jsonPath)) {
                    $data = $this->load(basename($dir));
                    if ($data) {
                        $data['_isCustom'] = $basePath === $this->customPath;
                        $data['_path'] = $dir;
                        $themes->push($data);
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Load a theme by slug.
     */
    public function load(string $slug): ?array
    {
        // Check custom path first
        $customJson = $this->customPath . '/' . $slug . '/theme.json';
        if (File::exists($customJson)) {
            return json_decode(File::get($customJson), true);
        }

        // Fallback to builtin
        $builtinJson = $this->builtinPath . '/' . $slug . '/theme.json';
        if (File::exists($builtinJson)) {
            return json_decode(File::get($builtinJson), true);
        }

        return null;
    }

    /**
     * Validate a theme.json structure.
     */
    public function validate(array $data): bool
    {
        return isset($data['meta']['slug'], $data['meta']['name'], $data['colors']);
    }

    /**
     * Set the active theme and invalidate cache.
     */
    public function setActive(string $slug): void
    {
        Option::set('theme_active', $slug);
        Cache::forget('theme.active');
    }

    /**
     * Import a theme from an uploaded ZIP file.
     */
    public function importFromZip(UploadedFile $file): string
    {
        $zip = new ZipArchive();
        $tmpPath = sys_get_temp_dir() . '/' . uniqid('theme_');

        if ($zip->open($file->getRealPath()) !== true) {
            throw new \RuntimeException('Cannot open ZIP file.');
        }

        $zip->extractTo($tmpPath);
        $zip->close();

        // Find theme.json inside extracted dir
        $jsonFiles = glob($tmpPath . '/*/theme.json');
        if (empty($jsonFiles)) {
            throw new \RuntimeException('No theme.json found in ZIP.');
        }

        $themeDir = dirname($jsonFiles[0]);
        $data = json_decode(File::get($jsonFiles[0]), true);

        if (!$this->validate($data)) {
            throw new \RuntimeException('Invalid theme.json structure.');
        }

        $slug = $data['meta']['slug'];

        // Check it's not overwriting a builtin theme
        if (File::isDirectory($this->builtinPath . '/' . $slug)) {
            throw new \RuntimeException('Cannot overwrite a built-in theme via import.');
        }

        $destPath = $this->customPath . '/' . $slug;
        File::ensureDirectoryExists($destPath);
        File::copyDirectory($themeDir, $destPath);
        File::deleteDirectory($tmpPath);

        return $slug;
    }

    /**
     * Export a theme as a downloadable ZIP.
     */
    public function exportToZip(string $slug): string
    {
        $sourcePath = $this->getThemePath($slug);
        $zipPath = sys_get_temp_dir() . '/theme_' . $slug . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (File::allFiles($sourcePath) as $file) {
            $zip->addFile($file->getRealPath(), $slug . '/' . $file->getRelativePathname());
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * Create a custom theme from form values.
     */
    public function createCustom(string $slug, array $values): void
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
        $destPath = $this->customPath . '/' . $slug;
        File::ensureDirectoryExists($destPath);
        File::put($destPath . '/theme.json', json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Delete a custom theme (cannot delete builtin themes).
     */
    public function delete(string $slug): void
    {
        if (File::isDirectory($this->builtinPath . '/' . $slug)) {
            throw new \RuntimeException('Cannot delete a built-in theme.');
        }

        $path = $this->customPath . '/' . $slug;
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }

        // Reset active theme if deleting the current one
        if (Option::get('theme_active') === $slug) {
            $this->setActive('hlstatsx-dark');
        }

        Cache::forget('theme.active');
    }

    /**
     * Duplicate an existing theme as a new custom theme.
     */
    public function duplicate(string $slug, string $newSlug): void
    {
        $source = $this->getThemePath($slug);
        $newSlug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($newSlug));
        $dest = $this->customPath . '/' . $newSlug;

        File::ensureDirectoryExists($dest);
        File::copyDirectory($source, $dest);

        // Update meta in theme.json
        $jsonPath = $dest . '/theme.json';
        if (File::exists($jsonPath)) {
            $data = json_decode(File::get($jsonPath), true);
            $data['meta']['slug'] = $newSlug;
            $data['meta']['name'] .= ' (Copy)';
            File::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private function getThemePath(string $slug): string
    {
        $customPath = $this->customPath . '/' . $slug;
        if (File::isDirectory($customPath)) {
            return $customPath;
        }

        $builtinPath = $this->builtinPath . '/' . $slug;
        if (File::isDirectory($builtinPath)) {
            return $builtinPath;
        }

        throw new \RuntimeException("Theme '{$slug}' not found.");
    }
}
