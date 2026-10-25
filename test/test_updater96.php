<?php
declare(strict_types=1);

if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "mysqli is required\n");
    exit(2);
}

function error($message, $exit = true)
{
    throw new RuntimeException((string)$message);
}

class Updater96TestDb
{
    private mysqli $connection;
    private mysqli_result|bool $lastResult = false;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function query(string $sql, bool $showError = true)
    {
        try {
            $result = $this->connection->query($sql);
        } catch (mysqli_sql_exception $exception) {
            if ($showError) {
                throw $exception;
            }
            return false;
        }
        if ($result === false && $showError) {
            throw new RuntimeException($this->connection->error);
        }
        $this->lastResult = $result;
        return $result;
    }

    public function fetch_array($result = null)
    {
        $result ??= $this->lastResult;
        return $result instanceof mysqli_result ? $result->fetch_array(MYSQLI_BOTH) : false;
    }

    public function free_result($result = null): void
    {
        $result ??= $this->lastResult;
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }

    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }
}

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$host = getenv('HLX_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('HLX_TEST_DB_PORT') ?: 3306);
$user = getenv('HLX_TEST_DB_USER') ?: 'root';
$password = getenv('HLX_TEST_DB_PASSWORD') ?: '';
$connection = new mysqli($host, $user, $password, '', $port);
$connection->set_charset('utf8mb4');
$database = 'hlx_ce_updater96_' . bin2hex(random_bytes(6));
$connection->query("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$connection->select_db($database);
$db = new Updater96TestDb($connection);
define('IN_UPDATER', true);

try {
    $fixtures = [
        "CREATE TABLE hlstats_Options (`keyname` varchar(50) PRIMARY KEY, `value` varchar(255) NOT NULL, `opttype` int NOT NULL DEFAULT 0)",
        "INSERT INTO hlstats_Options (`keyname`, `value`) VALUES ('dbversion', '95'), ('version', '1.11.6'), ('sitename', 'HLstatsX Community Edition')",
        "CREATE TABLE hlstats_Options_Choices (`keyname` varchar(50) NOT NULL, `value` varchar(255) NOT NULL, `text` varchar(255) NOT NULL, `isDefault` tinyint NOT NULL DEFAULT 0, PRIMARY KEY (`keyname`, `value`))",
        "CREATE TABLE hlstats_Servers (`serverId` int unsigned PRIMARY KEY)",
        "INSERT INTO hlstats_Servers VALUES (1), (2)",
        "CREATE TABLE hlstats_Users (`username` varchar(16) NOT NULL PRIMARY KEY, `password` varchar(32) NOT NULL DEFAULT '', `acclevel` int NOT NULL DEFAULT 0)",
        "INSERT INTO hlstats_Users (`username`, `password`, `acclevel`) VALUES ('legacy', 'e6d1a9a4f39a8a3d1c7c82a5f48644e4', 100)",
        "CREATE TABLE hlstats_Servers_Config_Default (`parameter` varchar(50) PRIMARY KEY, `value` varchar(128) NOT NULL, `description` mediumtext)",
        "CREATE TABLE hlstats_Servers_Config (`serverId` int unsigned NOT NULL, `parameter` varchar(50) NOT NULL, `value` varchar(128) NOT NULL, PRIMARY KEY (`serverId`, `parameter`))",
        "INSERT INTO hlstats_Servers_Config VALUES (1, 'LogSecret', 'keep-this-secret')",
        "CREATE TABLE hlstats_Events_Chat (`id` int unsigned NOT NULL AUTO_INCREMENT, `eventTime` datetime DEFAULT NULL, `serverId` int unsigned NOT NULL DEFAULT 0, `playerId` int unsigned NOT NULL DEFAULT 0, `message` varchar(128) NOT NULL DEFAULT '', PRIMARY KEY (`id`), KEY `playerId` (`playerId`), KEY `serverId` (`serverId`)) ENGINE=MyISAM",
        "CREATE TABLE hlstats_Heatmap_Config (`id` int unsigned NOT NULL AUTO_INCREMENT, `map` varchar(64) NOT NULL, `game` varchar(32) NOT NULL, `xoffset` float NOT NULL, `yoffset` float NOT NULL, `flipx` tinyint(1) NOT NULL DEFAULT 0, `flipy` tinyint(1) NOT NULL DEFAULT 1, `rotate` tinyint(1) NOT NULL DEFAULT 0, `days` tinyint NOT NULL DEFAULT 30, `brush` varchar(5) NOT NULL DEFAULT 'small', `scale` float NOT NULL, `font` tinyint NOT NULL DEFAULT 10, `thumbw` float NOT NULL DEFAULT 0.170312, `thumbh` float NOT NULL DEFAULT 0.170312, `cropx1` int NOT NULL DEFAULT 0, `cropy1` int NOT NULL DEFAULT 0, `cropx2` int NOT NULL DEFAULT 0, `cropy2` int NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `gamemap` (`map`, `game`))",
        "INSERT INTO hlstats_Heatmap_Config (`map`, `game`, `xoffset`, `yoffset`, `scale`) VALUES ('de_dust2', 'cs2', 999, 888, 1)",
        "INSERT INTO hlstats_Events_Chat (`eventTime`, `serverId`, `playerId`, `message`) VALUES ('2026-01-02 03:04:05', 1, 7, 'preserve chat')",
        "CREATE TABLE geoLiteCity_Location (`locId` bigint unsigned PRIMARY KEY, `country` varchar(2) NOT NULL, `region` varchar(50) DEFAULT NULL, `city` text DEFAULT NULL, `postalCode` varchar(10) DEFAULT NULL, `latitude` decimal(14,4) DEFAULT NULL, `longitude` decimal(14,4) DEFAULT NULL)",
        "INSERT INTO geoLiteCity_Location (`locId`, `country`, `region`, `city`) VALUES (1, 'US', 'California', 'San Francisco')",
    ];
    foreach ($fixtures as $sql) {
        $db->query($sql);
    }

    try {
        include __DIR__ . '/../src/web/updater/96.php';
        throw new RuntimeException('Expected updater to reject the unsupported city column type');
    } catch (RuntimeException $exception) {
        if (strpos($exception->getMessage(), 'unsupported GeoIP column type') === false) {
            throw $exception;
        }
    }
    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'dbversion'");
    assertSameValue('95', $db->fetch_array($result)['value'], 'failed migration must not advance dbversion');
    $db->query("ALTER TABLE geoLiteCity_Location MODIFY `city` varchar(50) DEFAULT NULL");

    include __DIR__ . '/../src/web/updater/96.php';
    include __DIR__ . '/../src/web/updater/96.php';

    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'dbversion'");
    assertSameValue('96', $db->fetch_array($result)['value'], 'successful migration dbversion');
    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'version'");
    assertSameValue('1.13.0-CE', $db->fetch_array($result)['value'], 'curated app version');
    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'sitename'");
    assertSameValue('HLstatsX: Curated Edition', $db->fetch_array($result)['value'], 'stock site name must be upgraded');
    $db->query("UPDATE hlstats_Options SET `value` = 'My Custom Stats' WHERE `keyname` = 'sitename'");
    include __DIR__ . '/../src/web/updater/96.php';
    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'sitename'");
    assertSameValue('My Custom Stats', $db->fetch_array($result)['value'], 'administrator site name must be preserved');
    $result = $db->query("SELECT `serverId`, `value` FROM hlstats_Servers_Config WHERE `parameter` = 'LogSecret' ORDER BY `serverId`");
    $secrets = $result->fetch_all(MYSQLI_ASSOC);
    assertSameValue('keep-this-secret', $secrets[0]['value'], 'existing LogSecret must be preserved');
    assertSameValue('', $secrets[1]['value'], 'new server LogSecret default');
    $result = $db->query("SHOW INDEX FROM hlstats_Events_Chat WHERE `Key_name` = 'playerId_eventTime'");
    $index = $result->fetch_all(MYSQLI_ASSOC);
    assertSameValue(['playerId', 'eventTime'], array_column($index, 'Column_name'), 'composite chat index columns');
    $result = $db->query("SELECT `message` FROM hlstats_Events_Chat WHERE `id` = 1");
    assertSameValue('preserve chat', $db->fetch_array($result)['message'], 'chat rows must be preserved');
    $result = $db->query("SELECT `region`, `city` FROM geoLiteCity_Location WHERE `locId` = 1");
    $geo = $db->fetch_array($result);
    assertSameValue('California', $geo['region'], 'GeoIP region data must be preserved');
    assertSameValue('San Francisco', $geo['city'], 'GeoIP city data must be preserved');
    $result = $db->query("SHOW FULL COLUMNS FROM hlstats_Users LIKE 'password'");
    assertSameValue('varchar(255)', strtolower($db->fetch_array($result)['Type']), 'admin password hash column width');
    $result = $db->query("SELECT `password` FROM hlstats_Users WHERE `username` = 'legacy'");
    assertSameValue('e6d1a9a4f39a8a3d1c7c82a5f48644e4', $db->fetch_array($result)['password'], 'existing admin password hash must be preserved');
    $result = $db->query("SELECT `value` FROM hlstats_Options WHERE `keyname` = 'counter_enabled'");
    assertSameValue('1', $db->fetch_array($result)['value'], 'page counters must remain enabled by default');
    $result = $db->query("SELECT COUNT(*) AS `count` FROM hlstats_Options_Choices WHERE `keyname` = 'counter_enabled'");
    assertSameValue(2, (int)$db->fetch_array($result)['count'], 'page counter option must include both choices');
    $result = $db->query("SELECT COUNT(*) AS `maps` FROM hlstats_Heatmap_Config WHERE `game` = 'cs2'");
    assertSameValue('14', (string)$db->fetch_array($result)['maps'], 'CS2 map defaults');
    $result = $db->query("SELECT `xoffset` FROM hlstats_Heatmap_Config WHERE `map` = 'de_dust2' AND `game` = 'cs2'");
    assertSameValue('999', (string)$db->fetch_array($result)['xoffset'], 'existing map calibration must be preserved');

    echo "DB96 retry, idempotency, version, index, LogSecret and row-preservation checks passed.\n";
} finally {
    $connection->query("DROP DATABASE IF EXISTS `$database`");
    $connection->close();
}
