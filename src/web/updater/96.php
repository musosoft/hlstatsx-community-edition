<?php
if (!defined('IN_UPDATER'))
{
    die('Do not access this file directly.');
}

$dbversion = 96;
$version = '1.13.0-CE';

if (!function_exists('hlx_updater96_query'))
{
    function hlx_updater96_query($sql)
    {
        global $db;
        $result = $db->query($sql, false);
        if ($result === false)
        {
            error('Database update 96 failed. The schema version was not advanced; correct the database issue and retry.');
            return false;
        }
        return $result;
    }
}

if (!function_exists('hlx_updater96_widen_geo_column'))
{
    function hlx_updater96_widen_geo_column($column)
    {
        global $db;
        $result = hlx_updater96_query("SHOW FULL COLUMNS FROM `geoLiteCity_Location` LIKE '" . $db->escape($column) . "'");
        if ($result === false)
        {
            return false;
        }

        $row = $db->fetch_array($result);
        $db->free_result($result);
        if (!$row)
        {
            error("Database update 96 cannot find GeoIP column `$column`; schema version was not advanced.");
            return false;
        }

        if (!preg_match('/^varchar\(([0-9]+)\)$/i', $row['Type'], $matches))
        {
            error("Database update 96 found an unsupported GeoIP column type for `$column`; schema version was not advanced.");
            return false;
        }

        // Never shrink a site-specific wider column.
        if ((int)$matches[1] >= 128)
        {
            return true;
        }

        $definition = 'VARCHAR(128)';
        if (!empty($row['Collation']))
        {
            $charsetResult = hlx_updater96_query("SELECT `CHARACTER_SET_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'geoLiteCity_Location' AND `COLUMN_NAME` = '" . $db->escape($column) . "'");
            if ($charsetResult === false)
            {
                return false;
            }
            $charsetRow = $db->fetch_array($charsetResult);
            $db->free_result($charsetResult);
            if (!$charsetRow || !preg_match('/^[A-Za-z0-9_]+$/', $charsetRow['CHARACTER_SET_NAME']) || !preg_match('/^[A-Za-z0-9_]+$/', $row['Collation']))
            {
                error("Database update 96 found an unsupported GeoIP character set or collation for `$column`; schema version was not advanced.");
                return false;
            }
            $definition .= ' CHARACTER SET `' . $charsetRow['CHARACTER_SET_NAME'] . '` COLLATE `' . $row['Collation'] . '`';
        }

        $definition .= ($row['Null'] === 'YES') ? ' NULL' : ' NOT NULL';
        if (array_key_exists('Default', $row))
        {
            if ($row['Default'] === null)
            {
                $definition .= ' DEFAULT NULL';
            }
            else
            {
                $definition .= " DEFAULT '" . $db->escape($row['Default']) . "'";
            }
        }

        return hlx_updater96_query("ALTER TABLE `geoLiteCity_Location` MODIFY `$column` $definition") !== false;
    }
}

// Add the option for new servers and fill only missing values on existing ones.
if (hlx_updater96_query("INSERT IGNORE INTO `hlstats_Servers_Config_Default` (`parameter`, `value`, `description`) VALUES ('LogSecret', '', 'Source engine only: match this to sv_logsecret on the game server to reject unsigned log packets. Empty disables validation. Reload the daemon after changing it.')") === false)
{
    return;
}
if (hlx_updater96_query("INSERT IGNORE INTO `hlstats_Servers_Config` (`serverId`, `parameter`, `value`) SELECT `serverId`, 'LogSecret', '' FROM `hlstats_Servers`") === false)
{
    return;
}

// Add the composite lookup index while preserving unrelated/custom indexes.
$result = hlx_updater96_query('SHOW INDEX FROM `hlstats_Events_Chat`');
if ($result === false)
{
    return;
}
$indexes = array();
while ($row = $db->fetch_array($result))
{
    $indexes[$row['Key_name']][(int)$row['Seq_in_index']] = $row['Column_name'];
}
$db->free_result($result);
foreach ($indexes as &$columns)
{
    ksort($columns);
    $columns = array_values($columns);
}
unset($columns);

$hasCompositeIndex = false;
foreach ($indexes as $columns)
{
    if ($columns === array('playerId', 'eventTime'))
    {
        $hasCompositeIndex = true;
        break;
    }
}

if (!$hasCompositeIndex)
{
    if (isset($indexes['playerId_eventTime']) && $indexes['playerId_eventTime'] !== array('playerId', 'eventTime'))
    {
        error('Database update 96 found a conflicting `playerId_eventTime` chat index and left it untouched; schema version was not advanced.');
        return;
    }

    if (isset($indexes['playerId']))
    {
        if ($indexes['playerId'] !== array('playerId'))
        {
            error('Database update 96 found a custom `playerId` chat index and left it untouched; schema version was not advanced.');
            return;
        }
        if (hlx_updater96_query('ALTER TABLE `hlstats_Events_Chat` DROP INDEX `playerId`') === false)
        {
            return;
        }
    }

    if (hlx_updater96_query('ALTER TABLE `hlstats_Events_Chat` ADD INDEX `playerId_eventTime` (`playerId`, `eventTime`)') === false)
    {
        return;
    }
}

if (!hlx_updater96_widen_geo_column('region') || !hlx_updater96_widen_geo_column('city'))
{
    return;
}

// password_hash() output is longer than legacy RIPEMD-128 hashes. Widen the
// storage column while preserving every existing hash value.
if (hlx_updater96_query("ALTER TABLE `hlstats_Users` MODIFY `password` VARCHAR(255) NOT NULL DEFAULT ''") === false)
{
    return;
}

// Preserve historical counters by default while letting high-traffic sites
// disable their two per-request writes without changing application code.
if (hlx_updater96_query("INSERT IGNORE INTO `hlstats_Options` (`keyname`, `value`, `opttype`) VALUES ('counter_enabled', '1', 2)") === false)
{
    return;
}
if (hlx_updater96_query("INSERT IGNORE INTO `hlstats_Options_Choices` (`keyname`, `value`, `text`, `isDefault`) VALUES ('counter_enabled', '1', 'Yes', 1), ('counter_enabled', '0', 'No', 0)") === false)
{
    return;
}

// Rename only the stock site label; preserve administrator-customized names.
if (hlx_updater96_query("UPDATE `hlstats_Options` SET `value` = 'HLstatsX: Curated Edition' WHERE `keyname` = 'sitename' AND `value` = 'HLstatsX Community Edition'") === false)
{
    return;
}

// Seed supported CS2 stock-map radar coordinates without overwriting any
// existing administrator calibration.
if (hlx_updater96_query("INSERT IGNORE INTO `hlstats_Heatmap_Config` (`map`, `game`, `xoffset`, `yoffset`, `flipx`, `flipy`, `rotate`, `days`, `brush`, `scale`, `font`, `thumbw`, `thumbh`, `cropx1`, `cropy1`, `cropx2`, `cropy2`) VALUES
    ('ar_baggage', 'cs2', 1316, 1288, 0, 1, 1, 30, 'small', 2.539062, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('ar_shoots', 'cs2', 1368, 1952, 0, 1, 0, 30, 'small', 2.687500, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('cs_italy', 'cs2', 2647, 2592, 0, 1, 0, 30, 'small', 4.600000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('cs_office', 'cs2', 1838, 1858, 0, 1, 0, 30, 'small', 4.100000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_ancient', 'cs2', 2953, 2164, 0, 1, 0, 30, 'small', 5.000000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_anubis', 'cs2', 2796, 3328, 0, 1, 0, 30, 'small', 5.220000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_dust2', 'cs2', 2476, 3239, 0, 1, 0, 30, 'small', 4.400000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_inferno', 'cs2', 2087, 3870, 0, 1, 0, 30, 'small', 4.900000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_mirage', 'cs2', 3230, 1713, 0, 1, 0, 30, 'small', 5.000000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_nuke', 'cs2', 3453, 2887, 0, 1, 0, 30, 'small', 7.000000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_overpass', 'cs2', 4831, 1781, 0, 1, 0, 30, 'small', 5.200000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_train', 'cs2', 2308, 2078, 0, 1, 0, 30, 'small', 4.082077, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('de_vertigo', 'cs2', 3168, 1762, 0, 1, 0, 30, 'small', 4.000000, 10, 0.170312, 0.170312, 0, 0, 0, 0),
    ('rush_001', 'cs2', 11240, 9944, 0, 1, 0, 30, 'small', 18.910156, 10, 0.170312, 0.170312, 0, 0, 0, 0)") === false)
{
    return;
}

if (hlx_updater96_query("UPDATE `hlstats_Options` SET `value` = CASE `keyname` WHEN 'version' THEN '$version' WHEN 'dbversion' THEN '$dbversion' END WHERE `keyname` IN ('version', 'dbversion')") === false)
{
    return;
}
print "Updated curated edition and database schema versions.<br />\n";
?>
