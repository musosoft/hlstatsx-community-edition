<?php
if (!defined('IN_UPDATER'))
{
    die('Do not access this file directly.');
}

$dbversion = 94;

// Historical side totals lack the roster and halftime evidence needed for safe
// map-bias learning. Preserve them, but leave them ineligible by default.
$db->query("ALTER TABLE `hlstats_MapTeamBalanceMatches`
    ADD COLUMN `eligible` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `ts_rounds`");

print "Updating database schema number.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
?>
