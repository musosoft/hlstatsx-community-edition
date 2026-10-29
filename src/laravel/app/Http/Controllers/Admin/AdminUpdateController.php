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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUpdateController extends Controller
{
    private const GITHUB_REPO    = 'Royal-Multi-Gamers/hlstatsx-community-edition-laravel';
    private const GITHUB_API     = 'https://api.github.com/repos/Royal-Multi-Gamers/hlstatsx-community-edition-laravel/releases/latest';
    private const CODELOAD_HOST  = 'https://codeload.github.com/';
    private const API_HOST       = 'https://api.github.com/';
    private const RELEASES_HOST  = 'https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel/releases/';

    /** Paths relative to base_path() that must never be overwritten during an update. */
    private const PROTECTED_PATHS = ['.env', 'storage', '.git', 'vendor', 'node_modules', 'public/storage'];

    // -------------------------------------------------------------------------

    public function index()
    {
        $versionInfo = $this->getVersionInfo();
        return view('admin.update.index', compact('versionInfo'));
    }

    /**
     * Streamed update endpoint (Server-Sent Events).
     * Emits real-time progress for download, extract, copy, migrate, composer and cache steps.
     */
    public function stream(): StreamedResponse
    {
        @set_time_limit(0);
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');

        return new StreamedResponse(function () {
            // Close every nested output buffer (Laravel, PHP-FPM default, etc.)
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            @ob_implicit_flush(true);

            // 4 KB SSE comment padding: forces browsers (Chrome) and most
            // reverse proxies to release their initial buffer immediately.
            echo ': ' . str_repeat(' ', 4096) . "\n\n";
            @flush();

            $send = function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                @flush();
            };

            try {
                Cache::forget('admin_update_check');
                $info = $this->getVersionInfo();

                if (! $info['latest']) {
                    $send('error', ['message' => __('Unable to reach GitHub.')]);
                    return;
                }
                if ($info['upToDate']) {
                    $send('error', ['message' => __('No update available.')]);
                    return;
                }

                $zipUrl = $this->resolveDownloadUrl($info['latest']);
                if (! $zipUrl ||
                    (! str_starts_with($zipUrl, self::API_HOST) &&
                     ! str_starts_with($zipUrl, self::CODELOAD_HOST) &&
                     ! str_starts_with($zipUrl, self::RELEASES_HOST))) {
                    $send('error', ['message' => __('Download URL not allowed.')]);
                    return;
                }

                $tempZip = storage_path('app/update.zip');
                $tempDir = storage_path('app/update_temp');
                $appRoot = base_path();

                // 1. Download with progress
                $send('step', ['key' => 'download', 'label' => __('Downloading :version', ['version' => $info['latestTag']])]);

                $lastPercent = -1;
                $progressCb  = function ($total, $downloaded) use ($send, &$lastPercent) {
                    if ($total <= 0) {
                        return;
                    }
                    $percent = (int) floor(($downloaded / $total) * 100);
                    if ($percent !== $lastPercent) {
                        $lastPercent = $percent;
                        $send('progress', [
                            'key'        => 'download',
                            'percent'    => $percent,
                            'downloaded' => $downloaded,
                            'total'      => $total,
                        ]);
                    }
                };

                $client = new \GuzzleHttp\Client();
                $client->request('GET', $zipUrl, [
                    'headers'   => ['User-Agent' => 'hlstatsx-ce-updater'],
                    'sink'      => $tempZip,
                    'timeout'   => 300,
                    'progress'  => function ($total, $down) use ($progressCb) {
                        $progressCb($total, $down);
                    },
                ]);

                $send('progress', ['key' => 'download', 'percent' => 100]);
                $send('log', ['message' => __('Archive downloaded (:size KB)', ['size' => round(filesize($tempZip) / 1024)])]);

                // 2. Extract
                $send('step', ['key' => 'extract', 'label' => __('Extracting archive')]);
                if (is_dir($tempDir)) {
                    $this->deleteDirectory($tempDir);
                }
                mkdir($tempDir, 0755, true);

                $zip = new \ZipArchive();
                if ($zip->open($tempZip) !== true) {
                    $send('error', ['message' => __('Unable to open the zip archive.')]);
                    return;
                }
                $zip->extractTo($tempDir);
                $zip->close();
                $send('progress', ['key' => 'extract', 'percent' => 100]);

                $sourceDir = $this->resolveExtractedSourceDir($tempDir);
                if ($sourceDir === null) {
                    $send('error', ['message' => __('Source directory not found in archive.')]);
                    return;
                }

                // 3. Copy files with progress
                $send('step', ['key' => 'copy', 'label' => __('Copying files')]);
                $copied = $this->copyDirectoryStreamed($sourceDir, $appRoot, $send);
                $send('progress', ['key' => 'copy', 'percent' => 100]);
                $send('log', ['message' => __(':count file(s) copied', ['count' => $copied])]);

                // 4. Migrations
                $send('step', ['key' => 'migrate', 'label' => __('Database migrations')]);
                Artisan::call('migrate', ['--force' => true]);
                $send('progress', ['key' => 'migrate', 'percent' => 100]);
                $send('log', ['message' => trim(Artisan::output()) ?: __('No pending migrations')]);

                // 5. Composer (optional, auto-installs composer.phar as fallback)
                $send('step', ['key' => 'composer', 'label' => 'composer install']);
                $composerOutput = $this->tryComposer($appRoot, $send);
                $send('progress', ['key' => 'composer', 'percent' => 100]);
                $send('log', [
                    'message' => $composerOutput !== null
                        ? 'composer : ' . $composerOutput
                        : '⚠ ' . __('composer skipped (binary not found and auto-install failed — run it manually)'),
                ]);

                // 6. Caches
                $send('step', ['key' => 'cache', 'label' => __('Rebuilding caches')]);

                // Wipe bootstrap caches that may reference removed/dev-only providers
                // (e.g. packages.php from a previous install with require-dev present).
                foreach (['packages.php', 'services.php', 'config.php', 'routes-v7.php', 'events.php'] as $cacheFile) {
                    $path = base_path('bootstrap/cache/' . $cacheFile);
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }

                Artisan::call('package:discover', ['--ansi' => true]);
                Artisan::call('optimize:clear');
                Artisan::call('optimize');
                Cache::forget('admin_update_check');
                $send('progress', ['key' => 'cache', 'percent' => 100]);
                $send('log', ['message' => __('Caches cleared and rebuilt')]);

                $send('done', [
                    'version' => $info['latestTag'],
                    'message' => __('Update to version :version applied successfully.', ['version' => $info['latestTag']]),
                ]);
            } catch (\Throwable $e) {
                $send('error', ['message' => __('Failed: :error', ['error' => $e->getMessage()])]);
            } finally {
                if (isset($tempZip) && file_exists($tempZip)) {
                    @unlink($tempZip);
                }
                if (isset($tempDir) && is_dir($tempDir)) {
                    $this->deleteDirectory($tempDir);
                }
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-store, no-transform, must-revalidate',
            'Pragma'            => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
            'Content-Encoding'  => 'none',
        ]);
    }

    public function apply()
    {
        // Force a fresh GitHub API call
        Cache::forget('admin_update_check');
        $info = $this->getVersionInfo();

        if (! $info['latest']) {
            return back()->with('error', __('Unable to reach GitHub to check for updates.'));
        }

        if ($info['upToDate']) {
            return back()->with('error', __('No update available, you are already on the latest version.'));
        }

        $zipUrl = $this->resolveDownloadUrl($info['latest']);

        // Security: only accept URLs from the official GitHub repo
        if (! $zipUrl ||
            (! str_starts_with($zipUrl, self::API_HOST) &&
             ! str_starts_with($zipUrl, self::CODELOAD_HOST) &&
             ! str_starts_with($zipUrl, self::RELEASES_HOST))) {
            return back()->with('error', __('Unexpected download URL, update aborted for security.'));
        }

        $tempZip = storage_path('app/update.zip');
        $tempDir = storage_path('app/update_temp');
        $appRoot = base_path();
        $log     = [];

        try {
            // 1. Download -------------------------------------------------------
            $log[] = __('Downloading version :version…', ['version' => $info['latestTag']]);
            $response = Http::timeout(180)
                ->withHeaders(['User-Agent' => 'hlstatsx-ce-updater'])
                ->get($zipUrl);

            if (! $response->successful()) {
                return back()->with('error', __('Download failed: HTTP :status', ['status' => $response->status()]));
            }

            file_put_contents($tempZip, $response->body());
            $log[] = __('Archive downloaded (:size KB)', ['size' => round(filesize($tempZip) / 1024)]);

            // 2. Extract --------------------------------------------------------
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            mkdir($tempDir, 0755, true);

            $zip = new \ZipArchive();
            if ($zip->open($tempZip) !== true) {
                return back()->with('error', __('Unable to open the zip archive.'));
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // 3. Locate the source directory inside the zip ---------------------
            $sourceDir = $this->resolveExtractedSourceDir($tempDir);
            if (! $sourceDir) {
                return back()->with('error', __('Source directory not found in archive.'));
            }
            $log[] = __('Archive extracted to: :dir', ['dir' => basename($sourceDir)]);

            // 4. Copy files (protected paths are skipped) -----------------------
            $copied = $this->copyDirectory($sourceDir, $appRoot);
            $log[] = __(':count file(s) copied', ['count' => $copied]);

            // 5. Run migrations -------------------------------------------------
            Artisan::call('migrate', ['--force' => true]);
            $log[] = __('Migrations executed: :output', ['output' => trim(Artisan::output())]);

            // 6. Try composer install (optional, auto-installs composer.phar as fallback)
            $composerOutput = $this->tryComposer($appRoot);
            if ($composerOutput !== null) {
                $log[] = 'composer install : ' . $composerOutput;
            } else {
                $log[] = '⚠ ' . __('composer install skipped (binary not found and auto-install failed — run it manually)');
            }

            // 7. Flush all caches -----------------------------------------------
            foreach (['packages.php', 'services.php', 'config.php', 'routes-v7.php', 'events.php'] as $cacheFile) {
                $path = base_path('bootstrap/cache/' . $cacheFile);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            Artisan::call('package:discover', ['--ansi' => true]);
            Artisan::call('optimize:clear');
            Artisan::call('optimize');
            Cache::forget('admin_update_check');
            $log[] = __('Caches cleared and rebuilt');

        } catch (\Throwable $e) {
            return back()->with('error', __('Update failed: :error', ['error' => $e->getMessage()]));
        } finally {
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }

        return redirect()->route('admin.update.index')
            ->with('update_success', [
                'version' => $info['latestTag'],
                'log'     => $log,
            ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function copyDirectory(string $source, string $dest): int
    {
        return $this->copyDirectoryStreamed($source, $dest, null);
    }

    private function copyDirectoryStreamed(string $source, string $dest, ?callable $send): int
    {
        $count       = 0;
        $sourceNorm  = rtrim(str_replace('\\', '/', $source), '/');

        // First pass: count files for progress %
        $total = 0;
        $iter1 = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter1 as $f) {
            if ($f->isFile()) {
                $total++;
            }
        }
        if ($total === 0) {
            $total = 1;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $lastPercent = -1;
        foreach ($iterator as $item) {
            $itemNorm = str_replace('\\', '/', $item->getPathname());
            $relative = ltrim(substr($itemNorm, strlen($sourceNorm)), '/');

            if ($relative === '') {
                continue;
            }

            // Skip protected paths
            foreach (self::PROTECTED_PATHS as $protected) {
                if ($relative === $protected || str_starts_with($relative, $protected . '/')) {
                    continue 2;
                }
            }

            $target = $dest . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $targetDir = dirname($target);
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getPathname(), $target);
                $count++;

                if ($send !== null) {
                    $percent = (int) floor(($count / $total) * 100);
                    if ($percent !== $lastPercent) {
                        $lastPercent = $percent;
                        $send('progress', [
                            'key'     => 'copy',
                            'percent' => $percent,
                            'current' => $count,
                            'total'   => $total,
                        ]);
                    }
                }
            }
        }

        return $count;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }

    private function tryComposer(string $appRoot, ?callable $send = null): ?string
    {
        // exec/shell_exec disabled via disable_functions?
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('shell_exec', $disabled, true) || in_array('exec', $disabled, true)) {
            return null;
        }

        $candidates = array_filter([
            // Highest priority: explicit override
            env('COMPOSER_BIN'),
            // Composer installed alongside the project
            $appRoot . '/composer.phar',
            $appRoot . '/composer',
            // Common system-wide locations
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            '/opt/composer/composer',
            // User-local locations (www-data home, current user home)
            getenv('HOME') ? rtrim(getenv('HOME'), '/') . '/.composer/composer.phar' : null,
            getenv('HOME') ? rtrim(getenv('HOME'), '/') . '/.local/bin/composer' : null,
            // Last resort: PATH lookup
            'composer',
            'composer.phar',
        ]);

        // Try to discover via `command -v` (POSIX) when PATH lookup might work
        $discovered = @shell_exec('command -v composer 2>/dev/null');
        if (is_string($discovered) && trim($discovered) !== '') {
            array_unshift($candidates, trim($discovered));
        }

        foreach ($candidates as $bin) {
            $resolved = $this->runComposerInstall($bin, $appRoot);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        // No composer found — try to auto-install composer.phar locally.
        if ($send) {
            $send('log', ['message' => __('Composer binary not found, attempting auto-install…')]);
        }
        $phar = $this->installComposerPhar($appRoot, $send);
        if ($phar !== null) {
            $resolved = $this->runComposerInstall($phar, $appRoot);
            if ($resolved !== null) {
                return __('auto-installed') . ' — ' . $resolved;
            }
        }

        return null;
    }

    /**
     * Try a single composer candidate: validate it with --version, then run
     * `composer install --no-dev --optimize-autoloader`. Returns the last few
     * lines of output on success, or null if the candidate is not a working
     * Composer binary.
     */
    private function runComposerInstall(string $bin, string $appRoot): ?string
    {
        // .phar files are executed through the PHP CLI
        $cmd = str_ends_with($bin, '.phar')
            ? ($this->resolvePhpCli() . ' ' . escapeshellarg($bin))
            : escapeshellcmd($bin);

        $test = @shell_exec($cmd . ' --version 2>&1');
        if (! $test || ! str_contains($test, 'Composer')) {
            return null;
        }

        $install = $cmd . ' install --no-dev --optimize-autoloader --no-interaction 2>&1';
        $out = [];
        @exec('cd ' . escapeshellarg($appRoot) . ' && ' . $install, $out);

        return implode(' | ', array_slice($out, -3)); // last 3 lines
    }

    /**
     * Fallback: download the official Composer installer from getcomposer.org
     * and create composer.phar in the project root. Returns the absolute path
     * on success, or null on failure.
     */
    private function installComposerPhar(string $appRoot, ?callable $send = null): ?string
    {
        if (! is_writable($appRoot)) {
            if ($send) {
                $send('log', ['message' => '⚠ ' . __('Project root is not writable, cannot install composer.phar')]);
            }
            return null;
        }

        $setupPath = $appRoot . '/composer-setup.php';
        $pharPath  = $appRoot . '/composer.phar';

        // Download the installer
        try {
            $client = new \GuzzleHttp\Client();
            $client->request('GET', 'https://getcomposer.org/installer', [
                'sink'    => $setupPath,
                'timeout' => 60,
                'headers' => ['User-Agent' => 'hlstatsx-ce-updater'],
            ]);
        } catch (\Throwable $e) {
            @unlink($setupPath);
            if ($send) {
                $send('log', ['message' => '⚠ ' . __('Failed to download Composer installer: :error', ['error' => $e->getMessage()])]);
            }
            return null;
        }

        if (! file_exists($setupPath) || filesize($setupPath) < 1024) {
            @unlink($setupPath);
            if ($send) {
                $send('log', ['message' => '⚠ ' . __('Composer installer download is invalid')]);
            }
            return null;
        }

        // Run the installer: php composer-setup.php --install-dir=... --filename=composer.phar
        $phpCli = $this->resolvePhpCli();
        $cmd = $phpCli
             . ' ' . escapeshellarg($setupPath)
             . ' --install-dir=' . escapeshellarg($appRoot)
             . ' --filename=composer.phar 2>&1';
        $out  = [];
        $code = 0;
        @exec($cmd, $out, $code);
        @unlink($setupPath);

        if ($code !== 0 || ! file_exists($pharPath)) {
            if ($send) {
                $send('log', [
                    'message' => '⚠ ' . __('Composer installer failed: :error', [
                        'error' => implode(' | ', array_slice($out, -3)) ?: 'exit ' . $code,
                    ]),
                ]);
            }
            return null;
        }

        @chmod($pharPath, 0755);

        if ($send) {
            $send('log', ['message' => __('Composer auto-installed at :path (using :php)', ['path' => $pharPath, 'php' => $phpCli])]);
        }

        return $pharPath;
    }

    /**
     * Resolve a usable PHP CLI binary.
     *
     * `PHP_BINARY` is unreliable under PHP-FPM (Plesk, php-fpm pools…): it
     * points to /opt/plesk/php/X.Y/sbin/php-fpm, which cannot execute scripts.
     * We probe common CLI paths derived from `PHP_VERSION` and fall back to
     * `command -v php` before defaulting to plain `php`.
     */
    private function resolvePhpCli(): string
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        $candidates = [];

        // Explicit override
        if ($override = env('PHP_CLI_BIN')) {
            $candidates[] = $override;
        }

        // If PHP_BINARY is NOT an FPM/CGI binary, prefer it
        if (defined('PHP_BINARY') && PHP_BINARY && ! preg_match('/(fpm|cgi)/i', PHP_BINARY)) {
            $candidates[] = PHP_BINARY;
        }

        // Common Plesk / Debian / Ubuntu / RHEL CLI paths, version-aware
        $version  = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; // e.g. 8.4
        $majMinor = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;       // e.g. 84

        $candidates = array_merge($candidates, [
            "/opt/plesk/php/{$version}/bin/php",
            "/opt/cpanel/ea-php{$majMinor}/root/usr/bin/php",
            "/usr/local/php{$version}/bin/php",
            "/usr/local/bin/php{$version}",
            "/usr/local/bin/php",
            "/usr/bin/php{$version}",
            "/usr/bin/php",
        ]);

        // Last-resort PATH lookups
        $discovered = @shell_exec('command -v php 2>/dev/null');
        if (is_string($discovered) && trim($discovered) !== '') {
            $candidates[] = trim($discovered);
        }
        $candidates[] = 'php';

        foreach (array_filter(array_unique($candidates)) as $candidate) {
            $cmd  = escapeshellcmd($candidate) . ' -v 2>&1';
            $test = @shell_exec($cmd);
            if (is_string($test) && stripos($test, '(cli)') !== false) {
                return $resolved = escapeshellcmd($candidate);
            }
        }

        // Nothing verified — fall back to plain `php` and hope PATH resolves it
        return $resolved = 'php';
    }

    /**
     * Pick the download URL for a release.
     *
     * The release workflow generates files (e.g. a version migration) AFTER
     * the tag is created, so GitHub's auto-generated `zipball_url` (which
     * snapshots the tag commit) does NOT contain those post-tag artifacts.
     * The workflow also uploads a ready-to-use `.zip` asset that DOES include
     * them — we prefer that asset when present and fall back to zipball_url.
     */
    private function resolveDownloadUrl(?array $release): ?string
    {
        if (! is_array($release)) {
            return null;
        }

        foreach ($release['assets'] ?? [] as $asset) {
            $name = strtolower($asset['name'] ?? '');
            $url  = $asset['browser_download_url'] ?? null;
            if ($url && str_ends_with($name, '.zip') && str_starts_with($url, self::RELEASES_HOST)) {
                return $url;
            }
        }

        return $release['zipball_url'] ?? null;
    }

    /**
     * Locate the Laravel project root inside an extracted update archive.
     *
     * The two archive layouts we accept:
     *   - zipball_url (GitHub auto-generated): files live in <repo-sha>/
     *   - uploaded asset (workflow `zip -r . ...`): files live at root
     *
     * We detect the layout by looking for a top-level `artisan` file.
     */
    private function resolveExtractedSourceDir(string $tempDir): ?string
    {
        // Flat asset zip: artisan is directly in $tempDir
        if (is_file($tempDir . '/artisan')) {
            return $tempDir;
        }

        // Zipball layout: a single subdirectory containing the project
        $entries = array_values(array_filter(
            (array) @scandir($tempDir),
            fn($e) => $e !== '.' && $e !== '..' && is_dir($tempDir . '/' . $e)
        ));

        foreach ($entries as $entry) {
            $candidate = $tempDir . '/' . $entry;
            if (is_file($candidate . '/artisan')) {
                return $candidate;
            }
        }

        // Last resort: pick the first subdirectory (legacy behaviour)
        return $entries ? $tempDir . '/' . $entries[0] : null;
    }

    private function getVersionInfo(): array
    {
        return self::fetchVersionInfo();
    }

    /**
     * Shared version-check used by both the admin UI and the artisan command.
     * Caches the GitHub API response for 1 hour under `admin_update_check`.
     * A GitHub PAT in `GITHUB_TOKEN` env raises the rate limit from 60/h to 5000/h.
     */
    public static function fetchVersionInfo(): array
    {
        // Use a dedicated key — the legacy Perl daemon overwrites `version`
        // at every startup with its own hardcoded value (currently 2.5.9).
        $installed = DB::table('hlstats_Options')->where('keyname', 'webapp_version')->value('value') ?? 'unknown';
        $installed = trim($installed);

        $latest = Cache::get('admin_update_check');

        if (! is_array($latest)) {
            $headers = ['User-Agent' => 'hlstatsx-ce-update-checker'];
            if ($token = config('services.github.token')) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders($headers)
                    ->get(self::GITHUB_API);

                if ($response->successful()) {
                    $latest = $response->json();
                    Cache::put('admin_update_check', $latest, 3600);
                } else {
                    // Diagnostic info — preserved across the call so caller can display it
                    $remaining = $response->header('X-RateLimit-Remaining');
                    $reset     = $response->header('X-RateLimit-Reset');
                    $reason    = __('HTTP :status', ['status' => $response->status()]);
                    if ($response->status() === 403 && $remaining === '0') {
                        $resetIn = $reset ? max(0, (int) $reset - time()) : null;
                        $reason  = __('GitHub rate limit exceeded (60 req/h per IP)')
                                 . ($resetIn !== null ? ', ' . __('resets in :seconds s', ['seconds' => $resetIn]) : '')
                                 . '. ' . __('Set GITHUB_TOKEN in .env to raise it to 5000/h.');
                    }
                    return [
                        'installed' => $installed,
                        'latest'    => null,
                        'upToDate'  => null,
                        'error'     => $reason,
                    ];
                }
            } catch (\Throwable $e) {
                return [
                    'installed' => $installed,
                    'latest'    => null,
                    'upToDate'  => null,
                    'error'     => __('Network error: :message', ['message' => $e->getMessage()]),
                ];
            }
        }

        if (! is_array($latest)) {
            return ['installed' => $installed, 'latest' => null, 'upToDate' => null];
        }

        $latestTag = ltrim($latest['tag_name'] ?? '', 'v');
        $upToDate  = version_compare($installed, $latestTag, '>=');

        return [
            'installed' => $installed,
            'latest'    => $latest,
            'latestTag' => $latestTag,
            'upToDate'  => $upToDate,
        ];
    }
}
