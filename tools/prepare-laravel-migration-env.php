<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fwrite(STDERR, "Run this helper with sudo on the production host.\n");
    exit(2);
}

$shared = '/opt/hlstatsx-laravel/shared';
$release = '/opt/hlstatsx-laravel/current';
$runtimeEnv = $shared.'/.env';
$credentialEnv = $shared.'/.env.migration.credentials';
$destination = $shared.'/.env.migration';
if (!is_file($runtimeEnv) || !is_file($credentialEnv) || !is_dir($shared)
    || is_link($credentialEnv) || (fileperms($credentialEnv) & 0077) !== 0) {
    fwrite(STDERR, "Protected Laravel runtime or migration credential environment is missing or too permissive.\n");
    exit(2);
}
if (file_exists($destination)) {
    fwrite(STDERR, "Refusing to replace the existing Laravel environment and rotate APP_KEY.\n");
    exit(2);
}

require $release.'/vendor/autoload.php';
$runtimeValues = Dotenv\Dotenv::parse((string) file_get_contents($runtimeEnv));
$migrationValues = Dotenv\Dotenv::parse((string) file_get_contents($credentialEnv));
if (empty($runtimeValues['APP_KEY'])) {
    fwrite(STDERR, "Existing Laravel APP_KEY is missing.\n");
    exit(2);
}

$dbHost = trim((string) ($migrationValues['DB_HOST'] ?? ''));
$dbPort = trim((string) ($migrationValues['DB_PORT'] ?? '3306'));
$dbName = trim((string) ($migrationValues['DB_DATABASE'] ?? ''));
$dbUser = (string) ($migrationValues['DB_USERNAME'] ?? '');
$dbPass = (string) ($migrationValues['DB_PASSWORD'] ?? '');
if (preg_match('/^(.+):([0-9]+)$/', $dbHost, $matches)) {
    $dbHost = $matches[1];
    $dbPort = $matches[2];
}
if ($dbHost === '' || !preg_match('/^[0-9]+$/', $dbPort)
    || !preg_match('/^[A-Za-z0-9_$-]+$/', $dbName) || $dbUser === '') {
    fwrite(STDERR, "Migration credential environment is incomplete.\n");
    exit(2);
}

$quote = static function (string $value): string {
    return '"'.str_replace(
        ["\\", '"', '$', "\n", "\r"],
        ["\\\\", '\\"', '\\$', '\\n', '\\r'],
        $value
    ).'"';
};

$values = [
    'APP_ENV' => 'migration',
    'APP_DEBUG' => 'false',
    'APP_KEY' => $runtimeValues['APP_KEY'],
    'APP_URL' => 'https://stats.lamateam.eu',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => $dbHost,
    'DB_PORT' => $dbPort,
    'DB_DATABASE' => $dbName,
    'DB_USERNAME' => $dbUser,
    'DB_PASSWORD' => $dbPass,
    'SESSION_DRIVER' => 'file',
    'CACHE_STORE' => 'file',
    'QUEUE_CONNECTION' => 'sync',
];

$contents = "# Generated from the protected Laravel migration credential environment.\n";
foreach ($values as $key => $value) {
    $contents .= $key.'='.$quote((string) $value)."\n";
}

if (file_put_contents($destination, $contents, LOCK_EX) === false
    || !chmod($destination, 0640)
    || !chgrp($destination, 'www-data')) {
    @unlink($destination);
    fwrite(STDERR, "Could not securely write Laravel runtime environment.\n");
    exit(1);
}

$releaseEnv = $release.'/.env.migration';
if (is_link($releaseEnv)) {
    if (readlink($releaseEnv) !== $destination) {
        fwrite(STDERR, "The release migration environment link points somewhere unexpected.\n");
        exit(1);
    }
} elseif (file_exists($releaseEnv) || !symlink($destination, $releaseEnv)) {
    @unlink($destination);
    fwrite(STDERR, "Could not link the protected migration environment into the Laravel release.\n");
    exit(1);
}

fwrite(STDOUT, "Temporary Laravel migration environment prepared separately from runtime config; credentials were not displayed.\n");
