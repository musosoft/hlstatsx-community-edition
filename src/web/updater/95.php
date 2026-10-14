<?php
if (!defined('IN_UPDATER'))
{
    die('Do not access this file directly.');
}

$dbversion = 95;

$db->query("CREATE TABLE IF NOT EXISTS `hlstats_MapTeamBalanceSwaps` (
    `serverId` int(10) unsigned NOT NULL,
    `map` varchar(64) NOT NULL,
    `mapStarted` int(10) unsigned NOT NULL,
    `uniqueId` varchar(64) NOT NULL,
    `swappedAt` int(10) unsigned NOT NULL,
    PRIMARY KEY (`serverId`,`map`,`mapStarted`,`uniqueId`),
    KEY `server_map_started` (`serverId`,`map`,`mapStarted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

print "Updating database schema number.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
?>
