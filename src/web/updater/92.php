<?php
    if ( !defined('IN_UPDATER') )
    {
        die('Do not access this file directly.');
    }

    $dbversion = 92;

    // Add a composite index for player counts by game and ranking visibility.
    $result = $db->query("SHOW INDEX FROM hlstats_Players WHERE Key_name = 'game_hideranking'");
    if ($db->num_rows($result) < 1)
    {
        print "Adding player-count database index.<br />";
        $db->query("
            ALTER TABLE
                hlstats_Players
                ADD INDEX `game_hideranking` (`game`, `hideranking`);
        ");
    }

    // Perform database schema update notification
    print "Updating database schema number.<br />";
    $db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
?>
