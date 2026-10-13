<?php
if (!defined('IN_UPDATER'))
{
    die('Do not access this file directly.');
}

$dbversion = 93;

// Keep completed per-map side results so ATB can discount established map bias.
$db->query("CREATE TABLE IF NOT EXISTS `hlstats_MapTeamBalanceMatches` (
    `serverId` int(10) unsigned NOT NULL,
    `map` varchar(64) NOT NULL,
    `mapStarted` int(10) unsigned NOT NULL,
    `ct_rounds` int(10) unsigned NOT NULL DEFAULT '0',
    `ts_rounds` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`serverId`,`map`,`mapStarted`),
    KEY `server_map` (`serverId`,`map`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

print "Updating database schema number.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
?>
