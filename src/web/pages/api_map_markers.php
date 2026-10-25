<?php

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

require_once(INCLUDE_PATH . '/map_markers.php');

$gameFilter = valid_game_code($_GET['game'] ?? '');
$where = 'lat IS NOT NULL AND lng IS NOT NULL AND ABS(lat) <= 90 AND ABS(lng) <= 180';
if ($gameFilter !== '') {
	$where .= " AND game='" . $db->escape($gameFilter) . "'";
}

$result = $db->query("SELECT serverId, game, name, IF(publicaddress != '', publicaddress, CONCAT(address, ':', port)) AS address, lat, lng, last_event FROM hlstats_Servers WHERE $where ORDER BY game, serverId");
$rows = array();
while ($row = $db->fetch_array($result)) {
	$rows[] = $row;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');
header('X-Content-Type-Options: nosniff');
echo hlxMapMarkersJson($rows);
exit;
