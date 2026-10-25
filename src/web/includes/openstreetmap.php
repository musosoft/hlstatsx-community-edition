<?php
/*
 * OpenStreetMap rendering for the legacy HLstatsX frontend.
 * Uses Leaflet and public OpenStreetMap raster tiles; no API key is required.
 * Keep attribution visible and follow https://operations.osmfoundation.org/policies/tiles/.
 */

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

function hlxOsmRegionCenter($region)
{
	$centers = array(
		'EUROPE' => array(48.8, 8.5, 3), 'NORTH AMERICA' => array(45.0, -97.0, 3),
		'SOUTH AMERICA' => array(-14.8, -61.2, 3), 'NORTH AFRICA' => array(25.4, 8.4, 4),
		'SOUTH AFRICA' => array(-29.0, 23.7, 5), 'NORTH EUROPE' => array(62.6, 15.4, 4),
		'EAST EUROPE' => array(51.9, 31.8, 4), 'GERMANY' => array(51.1, 10.1, 5),
		'FRANCE' => array(47.2, 2.4, 5), 'SPAIN' => array(40.3, -4.0, 5),
		'UNITED KINGDOM' => array(54.0, -4.3, 5), 'DENMARK' => array(56.1, 9.2, 6),
		'SWEDEN' => array(63.2, 16.3, 4), 'NORWAY' => array(65.6, 13.1, 4),
		'FINLAND' => array(65.1, 26.6, 4), 'NETHERLANDS' => array(52.3, 5.4, 7),
		'BELGIUM' => array(50.7, 4.5, 7), 'SUISSE' => array(46.8, 8.2, 7),
		'AUSTRIA' => array(47.7, 14.1, 7), 'POLAND' => array(52.1, 19.3, 6),
		'ITALY' => array(42.6, 12.7, 5), 'TURKEY' => array(39.0, 34.9, 6),
		'ROMANIA' => array(45.94, 24.96, 6), 'BRAZIL' => array(-12.0, -53.1, 4),
		'ARGENTINA' => array(-34.3, -65.7, 3), 'RUSSIA' => array(65.7, 98.8, 3),
		'ASIA' => array(20.4, 95.6, 3), 'CHINA' => array(36.2, 104.0, 4),
		'JAPAN' => array(36.2, 136.8, 5), 'SOUTH KOREA' => array(36.6, 127.8, 6),
		'TAIWAN' => array(23.6, 121.0, 7), 'AUSTRALIA' => array(-26.1, 134.8, 4),
		'CANADA' => array(60.0, -97.0, 3), 'WORLD' => array(25.0, 8.5, 2),
	);
	$key = strtoupper(trim((string)$region));
	return isset($centers[$key]) ? $centers[$key] : $centers['EUROPE'];
}

function hlxOsmJson($value)
{
	return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
}

function hlxPrintOsmMap($type = 'main')
{
	global $db, $game, $g_options, $clandata, $clan;
	$region = ($type === 'clan' && !empty($clandata['mapregion'])) ? $clandata['mapregion'] : ($g_options['google_map_region'] ?? 'EUROPE');
	$center = hlxOsmRegionCenter($region);
	$servers = array();
	$players = array();

	if ($type === 'main') {
		$gameSql = $db->escape($game);
		$result = $db->query("SELECT serverId, IF(publicaddress != '', publicaddress, CONCAT(address, ':', port)) AS addr, name, kills, lat, lng, city, country FROM hlstats_Servers WHERE game='$gameSql' AND lat IS NOT NULL AND lng IS NOT NULL");
		while ($row = $db->fetch_array($result)) {
			$lat = (float)$row['lat'];
			$lng = (float)$row['lng'];
			if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) continue;
			$key = $lat . ',' . $lng;
			if (!isset($servers[$key])) {
				$servers[$key] = array('lat' => $lat, 'lng' => $lng, 'city' => (string)$row['city'], 'country' => (string)$row['country'], 'servers' => array());
			}
			$servers[$key]['servers'][] = array('id' => (int)$row['serverId'], 'addr' => (string)$row['addr'], 'name' => (string)$row['name'], 'kills' => (int)$row['kills']);
		}

		$result = $db->query("SELECT l.player_id, l.name, l.kills, l.deaths, l.connected, l.cli_lat AS lat, l.cli_lng AS lng, l.cli_city AS city, l.cli_country AS country FROM hlstats_Livestats l INNER JOIN hlstats_Servers s ON s.serverId=l.server_id WHERE l.cli_lat IS NOT NULL AND l.cli_lng IS NOT NULL AND s.game='$gameSql'");
		while ($row = $db->fetch_array($result)) {
			$lat = (float)$row['lat'];
			$lng = (float)$row['lng'];
			if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) continue;
			$key = $lat . ',' . $lng;
			if (!isset($players[$key])) {
				$players[$key] = array('lat' => $lat, 'lng' => $lng, 'city' => (string)$row['city'], 'country' => (string)$row['country'], 'players' => array());
			}
			$players[$key]['players'][] = array('id' => (int)$row['player_id'], 'name' => (string)$row['name'], 'kills' => (int)$row['kills'], 'deaths' => (int)$row['deaths'], 'connected' => (int)$row['connected']);
		}
	} elseif ($type === 'clan') {
		$clanId = (int)$clan;
		$result = $db->query("SELECT playerId, lastName, country, kills, deaths, lat, lng, city FROM hlstats_Players WHERE clan=$clanId AND hideranking=0 AND lat IS NOT NULL AND lng IS NOT NULL GROUP BY playerId");
		while ($row = $db->fetch_array($result)) {
			$lat = (float)$row['lat'];
			$lng = (float)$row['lng'];
			if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) continue;
			$key = $lat . ',' . $lng;
			if (!isset($players[$key])) {
				$players[$key] = array('lat' => $lat, 'lng' => $lng, 'city' => (string)$row['city'], 'country' => (string)$row['country'], 'players' => array());
			}
			$players[$key]['players'][] = array('id' => (int)$row['playerId'], 'name' => (string)$row['lastName'], 'kills' => (int)$row['kills'], 'deaths' => (int)$row['deaths']);
		}
	}

	$servers = array_values($servers);
	$players = array_values($players);
	?>
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
	<div id="map" class="hlx-osm-map" role="region" aria-label="Server and player locations map"></div>
	<script>
	(function () {
		var serverLocations = <?php echo hlxOsmJson($servers); ?>;
		var playerLocations = <?php echo hlxOsmJson($players); ?>;
		var map = L.map('map', {scrollWheelZoom: false}).setView([<?php echo $center[0]; ?>, <?php echo $center[1]; ?>], <?php echo (int)$center[2]; ?>);
		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(map);

		function popupFor(location, kind) {
			var box = document.createElement('div');
			var title = document.createElement('strong');
			title.textContent = [location.city, location.country].filter(Boolean).join(', ') || 'Location';
			box.appendChild(title);
			var items = kind === 'server' ? location.servers : location.players;
			items.forEach(function (item) {
				var row = document.createElement('p');
				var link = document.createElement('a');
				link.href = kind === 'server'
					? 'hlstats.php?mode=servers&server_id=' + encodeURIComponent(item.id) + '&game=' + encodeURIComponent(<?php echo hlxOsmJson((string)$game); ?>)
					: 'hlstats.php?mode=playerinfo&player=' + encodeURIComponent(item.id);
				link.textContent = item.name || (kind === 'server' ? 'Game server' : 'Player');
				row.appendChild(link);
				if (kind === 'server') {
					row.appendChild(document.createTextNode(' — ' + item.addr + ' (' + item.kills + ' kills)'));
				} else {
					row.appendChild(document.createTextNode(' — ' + item.kills + ':' + item.deaths));
				}
				box.appendChild(row);
			});
			return box;
		}

		serverLocations.forEach(function (location) {
			L.circleMarker([location.lat, location.lng], {radius: 8, color: '#9f2525', fillColor: '#e04b45', fillOpacity: 0.85})
				.addTo(map).bindPopup(popupFor(location, 'server'));
		});
		playerLocations.forEach(function (location) {
			L.circleMarker([location.lat, location.lng], {radius: 6, color: '#1556a0', fillColor: '#4b91df', fillOpacity: 0.8})
				.addTo(map).bindPopup(popupFor(location, 'player'));
		});
		window.setTimeout(function () { map.invalidateSize(); }, 100);
	})();
	</script>
	<?php
}
?>
