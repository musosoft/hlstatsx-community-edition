<?php

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

function hlxMapMarkerPayload($row, $now = null)
{
	$lat = isset($row['lat']) ? (float)$row['lat'] : NAN;
	$lng = isset($row['lng']) ? (float)$row['lng'] : NAN;
	if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) {
		return null;
	}

	$now = $now === null ? time() : (int)$now;
	$lastEvent = isset($row['last_event']) ? (int)$row['last_event'] : 0;
	return array(
		'id' => (int)$row['serverId'],
		'game' => (string)$row['game'],
		'lat' => $lat,
		'lng' => $lng,
		'name' => (string)$row['name'],
		'address' => (string)$row['address'],
		'online' => $lastEvent >= ($now - 300),
	);
}

function hlxMapMarkersJson($rows, $now = null)
{
	$markers = array();
	foreach ($rows as $row) {
		$marker = hlxMapMarkerPayload($row, $now);
		if ($marker !== null) {
			$markers[] = $marker;
		}
	}
	return json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
}
