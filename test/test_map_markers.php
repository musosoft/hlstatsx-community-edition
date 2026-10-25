<?php
declare(strict_types=1);

define('IN_HLSTATS', true);
require __DIR__ . '/../src/web/includes/map_markers.php';

$json = hlxMapMarkersJson(array(
	array('serverId' => 4, 'game' => 'css', 'name' => '<server>', 'address' => 'stats.example:27015', 'lat' => '37.7', 'lng' => '-122.4', 'last_event' => 9700, 'rcon_password' => 'must-not-leak'),
	array('serverId' => 5, 'game' => 'css', 'name' => 'invalid', 'address' => '127.0.0.1:27016', 'lat' => '91', 'lng' => '0', 'last_event' => 9700),
), 10000);
$markers = json_decode($json, true);

if (!is_array($markers) || count($markers) !== 1) {
	fwrite(STDERR, "Map API should omit invalid coordinates.\n");
	exit(1);
}
if ($markers[0]['id'] !== 4 || $markers[0]['online'] !== true || $markers[0]['name'] !== '<server>' || isset($markers[0]['rcon_password'])) {
	fwrite(STDERR, "Map API should return safe public marker data only.\n");
	exit(1);
}

echo "Map marker API checks passed.\n";
