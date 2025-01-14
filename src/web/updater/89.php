<?php
if ( !defined('IN_UPDATER') )
{
    die('Do not access this file directly.');
}

$dbversion = 89;
$version = "1.11.4";

$db->query("INSERT INTO `hlstats_Servers_Config_Default` (`parameter`, `value`, `description`) VALUES
('BalanceSwitchOnlyDead', '1', 'If enabled, only dead players can be switched by AutoBalance 1=on(default) 0=off.'),
('BalanceStartRounds', '7', 'Minimal round count after map change, when AutoBalance by skill should start. Value lower than 2 is ignored. Default is 7.'),
('BalanceRoundsDelay', '3', 'Rounds before next AutoBalance by skill. Default is 3.'),
('BalanceAnalyzeRounds', '7', 'How many last rounds are analyzed for balancing. Default is 7.'),
('BalanceMaxWins', '1', 'Defines maximum wins trough BalanceAnalyzeRounds for team to balance. More wins = more aggressive balance. Default 1.'),
('BalanceIgnoreBots', '0', 'If enabled, bots are ignored by AutoBalance. 1=on 0=off(default).')");

$db->query("INSERT INTO `hlstats_Games_Defaults` (`code`, `parameter`, `value`) VALUES
('css', 'BalanceSwitchOnlyDead', '1'),
('css', 'BalanceStartRounds', '7'),
('css', 'BalanceRoundsDelay', '3'),
('css', 'BalanceIgnoreBots', '0'),
('cstrike', 'BalanceSwitchOnlyDead', '1'),
('cstrike', 'BalanceStartRounds', '7'),
('cstrike', 'BalanceRoundsDelay', '3'),
('cstrike', 'BalanceIgnoreBots', '0'),
('csp', 'BalanceSwitchOnlyDead', '1'),
('csp', 'BalanceStartRounds', '7'),
('csp', 'BalanceRoundsDelay', '3'),
('csp', 'BalanceIgnoreBots', '0'),
('csgo', 'BalanceSwitchOnlyDead', '1'),
('csgo', 'BalanceStartRounds', '7'),
('csgo', 'BalanceRoundsDelay', '3'),
('csgo', 'BalanceIgnoreBots', '0'),
('cs2', 'UpdateHostname', '1'),
('cs2', 'BalanceSwitchOnlyDead', '1'),
('cs2', 'BalanceStartRounds', '7'),
('cs2', 'BalanceRoundsDelay', '3'),
('cs2', 'BalanceIgnoreBots', '0')");

// Perform database schema update notification
print "Updating database and verion schema numbers.<br />";
$db->query("UPDATE hlstats_Options SET `value` = '$version' WHERE `keyname` = 'version'");
$db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
