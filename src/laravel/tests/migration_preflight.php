<?php

// Prove the CLI migration guard rejects DB95 before Laravel creates its
// migration repository or any support tables. Uses the explicit disposable
// admin_accounts_test database when a MySQL/MariaDB host is supplied.
$mysqlHost = getenv('ADMIN_TEST_MYSQL_HOST');
$mysql = (bool) $mysqlHost;
if ($mysql && getenv('ADMIN_TEST_MYSQL_ALLOW_RESET') !== '1') {
    throw new RuntimeException('Set ADMIN_TEST_MYSQL_ALLOW_RESET=1 only for disposable admin_accounts_test.');
}

$database = null;
if ($mysql) {
    $databaseName = 'admin_accounts_test';
    $password = getenv('ADMIN_TEST_MYSQL_PASSWORD') ?: '';
    $port = getenv('ADMIN_TEST_MYSQL_PORT') ?: '3306';
    $connection = new PDO("mysql:host=$mysqlHost;port=$port;dbname=$databaseName", 'root', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($connection->query('SELECT DATABASE()')->fetchColumn() !== $databaseName) {
        throw new RuntimeException('Refusing to reset a database other than admin_accounts_test.');
    }
    $connection->exec('DROP TABLE IF EXISTS migrations, hlstats_Options');
    $connection->exec('CREATE TABLE hlstats_Options (keyname VARCHAR(64) PRIMARY KEY, value VARCHAR(255))');
    $connection->exec("INSERT INTO hlstats_Options VALUES ('dbversion', '95')");
} else {
    $database = tempnam(sys_get_temp_dir(), 'hlx-migrate-preflight-');
    $connection = new PDO('sqlite:'.$database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection->exec('CREATE TABLE hlstats_Options (keyname TEXT PRIMARY KEY, value TEXT)');
    $connection->exec("INSERT INTO hlstats_Options VALUES ('dbversion', '95')");
}

$environment = array_merge(getenv(), [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'DB_CONNECTION' => $mysql ? 'mysql' : 'sqlite',
    'DB_DATABASE' => $mysql ? $databaseName : $database,
]);
if ($mysql) {
    $environment = array_merge($environment, [
        'DB_HOST' => $mysqlHost,
        'DB_PORT' => getenv('ADMIN_TEST_MYSQL_PORT') ?: '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => $password,
    ]);
}
$process = proc_open(
    [PHP_BINARY, __DIR__.'/../artisan', 'migrate', '--force'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    dirname(__DIR__),
    $environment
);
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$status = proc_close($process);

$migrationsExists = $mysql
    ? (bool) $connection->query("SHOW TABLES LIKE 'migrations'")->fetchColumn()
    : (bool) $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'")->fetchColumn();
$connection = null;
if ($database !== null) {
    unlink($database);
}

if ($status !== 1 || ! str_contains($stderr, 'official DB96 input') || $migrationsExists) {
    throw new RuntimeException("Expected a pre-DDL DB95 rejection. status=$status stdout=$stdout stderr=$stderr migrations=".($migrationsExists ? 'present' : 'absent'));
}

if ($mysql) {
    $connection = new PDO("mysql:host=$mysqlHost;port=$port;dbname=$databaseName", 'root', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection->exec('DROP TABLE IF EXISTS hlstats_Options');
}

echo 'PASS: DB95 migration rejected before DDL ('.($mysql ? 'MySQL/MariaDB' : 'SQLite').")\n";
