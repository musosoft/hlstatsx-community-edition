<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 * Copyright (C) 2025-2026 Royal-Multi-Gamers
 * Licensed under the GNU General Public License v2.0
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    // -------------------------------------------------------------------------
    // Step 1 – Database configuration
    // -------------------------------------------------------------------------
    public function step1()
    {
        $this->ensureRuntimeDirectories();
        return view('install.step1');
    }

    public function step1Post(Request $request)
    {
        $data = $request->validate([
            'db_host'     => ['required', 'string'],
            'db_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        // Test the connection before writing .env
        try {
            $pdo = new \PDO(
                "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']};charset=utf8mb4",
                $data['db_username'],
                $data['db_password'] ?? '',
                [\PDO::ATTR_TIMEOUT => 5]
            );
        } catch (\PDOException $e) {
            return back()->withInput()->withErrors(['db_host' => 'Connexion échouée : ' . $e->getMessage()]);
        }

        // Persist to .env
        $this->updateEnv([
            'DB_HOST'     => $data['db_host'],
            'DB_PORT'     => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        // Store in session for next steps
        session([
            'install_db' => [
                'host'     => $data['db_host'],
                'port'     => $data['db_port'],
                'database' => $data['db_database'],
                'username' => $data['db_username'],
                'password' => $data['db_password'] ?? '',
            ]
        ]);

        return redirect()->route('install.step2');
    }

    // -------------------------------------------------------------------------
    // Step 2 – Import SQL schema
    // -------------------------------------------------------------------------
    public function step2()
    {
        $this->ensureRuntimeDirectories();

        // Check if tables already exist
        try {
            $tablesExist = Schema::hasTable('hlstats_Games');
        } catch (\Exception $e) {
            $tablesExist = false;
        }

        return view('install.step2', compact('tablesExist'));
    }

    public function step2Post(Request $request)
    {
        $action = $request->input('action', 'import');

        if ($action === 'skip') {
            return redirect()->route('install.step3');
        }

        // Run the install.sql
        $sqlPath = database_path('install.sql');

        if (!file_exists($sqlPath)) {
            return back()->withErrors(['sql' => 'Fichier install.sql introuvable dans database/.']);
        }

        try {
            $db = session('install_db');
            $pdo = new \PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",
                $db['username'],
                $db['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $sql = file_get_contents($sqlPath);

            // Split on semicolons (skip empty)
            $statements = array_filter(
                array_map('trim', preg_split('/;\s*\n/', $sql)),
                fn($s) => $s !== ''
            );

            $count = 0;
            foreach ($statements as $statement) {
                $pdo->exec($statement);
                $count++;
            }
        } catch (\PDOException $e) {
            return back()->withErrors(['sql' => 'Erreur SQL : ' . $e->getMessage()]);
        }

        return redirect()->route('install.step3')->with('sql_imported', $count . ' instructions exécutées.');
    }

    // -------------------------------------------------------------------------
    // Step 3 – Run Laravel migrations
    // -------------------------------------------------------------------------
    public function step3()
    {
        $this->ensureRuntimeDirectories();
        return view('install.step3');
    }

    public function step3Post(Request $request)
    {
        try {
            // Reconnect with updated .env
            $this->reconnectDb();
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
        } catch (\Exception $e) {
            return back()->withErrors(['migrate' => 'Erreur migration : ' . $e->getMessage()]);
        }

        return redirect()->route('install.step4')->with('migrate_output', $output);
    }

    // -------------------------------------------------------------------------
    // Step 4 – Create first admin user
    // -------------------------------------------------------------------------
    public function step4()
    {
        $this->ensureRuntimeDirectories();
        return view('install.step4');
    }

    public function step4Post(Request $request)
    {
        $data = $request->validate([
            'username'              => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'app_url'               => ['required', 'url'],
        ]);

        try {
            $this->reconnectDb();

            // Create the superadmin
            DB::table('hlstats_Admins')->insert([
                'username'    => $data['username'],
                'password'    => Hash::make($data['password']),
                'game'        => '',
                'serverID'    => 0,
                'accessLevel' => 'superadmin',
            ]);

            // Persist APP_URL
            $this->updateEnv(['APP_URL' => $data['app_url']]);

            // Mark as installed (fichier marqueur — indépendant du cache config)
            file_put_contents(storage_path('framework/installed'), date('Y-m-d H:i:s'));

            // Clear caches
            Artisan::call('optimize:clear');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['username' => 'Erreur : ' . $e->getMessage()]);
        }

        session()->forget('install_db');

        return redirect()->route('install.done');
    }

    public function done()
    {
        return view('install.done');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function updateEnv(array $values): void
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);

        foreach ($values as $key => $value) {
            $value = $this->envEscape((string)$value);

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($path, $content);
    }

    private function envEscape(string $value): string
    {
        if (preg_match('/\s|[#"\'\\\\]/', $value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }
        return $value;
    }

    private function reconnectDb(): void
    {
        // Force Laravel to re-read the updated .env DB config
        $db = session('install_db');
        if ($db) {
            config([
                'database.connections.mysql.host'     => $db['host'],
                'database.connections.mysql.port'     => $db['port'],
                'database.connections.mysql.database' => $db['database'],
                'database.connections.mysql.username' => $db['username'],
                'database.connections.mysql.password' => $db['password'],
            ]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
    }

    private function ensureRuntimeDirectories(): void
    {
        $paths = [
            storage_path('framework'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/testing'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }
    }
}
