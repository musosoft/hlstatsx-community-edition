<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fwrite(STDERR, "Run this helper with sudo on the production host.\n");
    exit(2);
}

$root = '/opt/hlstatsx-laravel/current';
$envFile = '/opt/hlstatsx-laravel/shared/.env';
if (!is_file($root.'/vendor/autoload.php') || !is_file($envFile)) {
    fwrite(STDERR, "Laravel release or protected runtime environment is missing.\n");
    exit(2);
}

require $root.'/vendor/autoload.php';
$values = is_file($envFile)
    ? Dotenv\Dotenv::parse((string) file_get_contents($envFile))
    : [];
$trustedProxies = trim((string) ($values['TRUSTED_PROXIES'] ?? ''));
if ($trustedProxies === '' || $trustedProxies === '*') {
    fwrite(STDERR, "Set TRUSTED_PROXIES to the exact reverse-proxy IP/CIDR allowlist before creating the runtime environment.\n");
    exit(2);
}
$appKey = $values['APP_KEY'] ?? ('base64:'.base64_encode(random_bytes(32)));

$dbHost = trim((string) ($values['DB_HOST'] ?? ''));
$dbPort = trim((string) ($values['DB_PORT'] ?? '3306'));
$dbName = trim((string) ($values['DB_DATABASE'] ?? ''));
if (preg_match('/^(.+):([0-9]+)$/', $dbHost, $matches)) {
    $dbHost = $matches[1];
    $dbPort = $matches[2];
}
if (!in_array($dbHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
    || !preg_match('/^[A-Za-z0-9_$-]+$/', $dbName)
    || !preg_match('/^[0-9]+$/', $dbPort)) {
    fwrite(STDERR, "Refusing to create a runtime account for a non-loopback database host or invalid schema name.\n");
    exit(2);
}

$grantHost = $dbHost === 'localhost' ? 'localhost' : trim($dbHost, '[]');
$username = 'hlx_laravel_runtime';
$password = rtrim(strtr(base64_encode(random_bytes(36)), '+/', '-_'), '=');
mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect('localhost', 'root', '', 'mysql');
if (!$db) {
    fwrite(STDERR, "Could not connect to MySQL through the protected local root socket to create the runtime account.\n");
    exit(2);
}

$created = false;
try {
    $quotedPassword = $db->real_escape_string($password);
    $quotedUser = "'".$db->real_escape_string($username)."'@'".$db->real_escape_string($grantHost)."'";
    if (!$db->query("CREATE USER $quotedUser IDENTIFIED BY '$quotedPassword'")) {
        throw new RuntimeException('Could not create the dedicated Laravel runtime account; it may already exist.');
    }
    $created = true;

    if (!$db->query("GRANT SELECT, INSERT, UPDATE, DELETE ON `".str_replace('`', '``', $dbName)."`.* TO $quotedUser")) {
        throw new RuntimeException('Could not grant the Laravel runtime account least-privilege data access.');
    }

    $runtimeDb = @mysqli_connect($dbHost, $username, $password, $dbName, (int) $dbPort);
    if (!$runtimeDb) {
        throw new RuntimeException('The new Laravel runtime account could not authenticate to its schema.');
    }
    $grants = $runtimeDb->query('SHOW GRANTS FOR CURRENT_USER()');
    $grantText = '';
    while ($grants && ($grant = $grants->fetch_row())) {
        $grantText .= ' '.strtoupper($grant[0]);
    }
    mysqli_close($runtimeDb);
    if (!str_contains($grantText, 'SELECT')
        || !str_contains($grantText, 'INSERT')
        || !str_contains($grantText, 'UPDATE')
        || !str_contains($grantText, 'DELETE')
        || preg_match('/\b(CREATE|ALTER|DROP|INDEX|GRANT OPTION)\b/', $grantText)) {
        throw new RuntimeException('The Laravel runtime account grants did not match the expected DML-only policy.');
    }

    $values['DB_CONNECTION'] = 'mysql';
    $values['APP_ENV'] = 'production';
    $values['APP_DEBUG'] = 'false';
    $values['APP_KEY'] = $appKey;
    $values['APP_URL'] = 'https://stats.lamateam.eu';
    unset($values['ASSET_URL']);
    $values['DB_HOST'] = $dbHost;
    $values['DB_PORT'] = $dbPort;
    $values['DB_DATABASE'] = $dbName;
    $values['DB_USERNAME'] = $username;
    $values['DB_PASSWORD'] = $password;
    $values['SESSION_DRIVER'] = 'file';
    $values['SESSION_SECURE_COOKIE'] = 'true';
    $values['CACHE_STORE'] = 'file';
    $values['QUEUE_CONNECTION'] = 'sync';
    $quote = static fn (string $value): string => '"'.str_replace(
        ["\\", '"', '$', "\n", "\r"],
        ["\\\\", '\\"', '\\$', '\\n', '\\r'],
        $value
    ).'"';
    $contents = "# Least-privilege Laravel runtime configuration.\n";
    foreach ($values as $key => $value) {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', (string) $key)) {
            continue;
        }
        $contents .= $key.'='.$quote((string) $value)."\n";
    }

    $temporary = $envFile.'.new';
    if (file_put_contents($temporary, $contents, LOCK_EX) === false
        || !chmod($temporary, 0640)
        || !chgrp($temporary, 'www-data')
        || !rename($temporary, $envFile)) {
        @unlink($temporary);
        throw new RuntimeException('Could not atomically write the least-privilege Laravel environment.');
    }
} catch (Throwable $error) {
    if ($created) {
        $db->query("DROP USER IF EXISTS $quotedUser");
    }
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
} finally {
    mysqli_close($db);
}

fwrite(STDOUT, "Dedicated Laravel runtime account created with schema-scoped DML grants; credentials were not displayed.\n");
