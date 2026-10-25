<?php

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

function hlxSitemapUrl($scriptUrl, $mode, $params = array())
{
	return $scriptUrl . '?' . http_build_query(array('mode' => $mode) + $params, '', '&', PHP_QUERY_RFC3986);
}

function hlxSitemapUrls($scriptUrl, $games, $details = array())
{
	$scriptUrl = preg_replace('/[?#].*$/', '', (string)$scriptUrl);
	$urls = array($scriptUrl);
	foreach ($games as $gameRow) {
		$gameCode = (string)$gameRow['code'];
		if (!preg_match('/^[A-Za-z0-9_.-]{1,32}$/D', $gameCode)) {
			continue;
		}
		foreach (array('players', 'clans', 'servers', 'maps', 'awards') as $mode) {
			$urls[] = hlxSitemapUrl($scriptUrl, $mode, array('game' => $gameCode));
		}
	}
	$allowedKeys = array('playerinfo' => 'player', 'claninfo' => 'clan', 'servers' => 'server_id', 'weaponinfo' => 'weapon', 'mapinfo' => 'map');
	foreach ($details as $detail) {
		$mode = (string)($detail['mode'] ?? '');
		$key = $allowedKeys[$mode] ?? '';
		$gameCode = (string)($detail['game'] ?? '');
		$value = $detail['value'] ?? '';
		if ($key === '' || !preg_match('/^[A-Za-z0-9_.-]{1,32}$/D', $gameCode)) {
			continue;
		}
		if (in_array($mode, array('playerinfo', 'claninfo', 'servers'), true)) {
			if (!is_numeric($value) || (int)$value < 1) continue;
			$value = (string)(int)$value;
		} else {
			$value = (string)$value;
			if ($value === '' || strlen($value) > 128) continue;
		}
		$urls[] = hlxSitemapUrl($scriptUrl, $mode, array($key => $value, 'game' => $gameCode));
	}
	return array_values(array_unique($urls));
}

function hlxSitemapXml($scriptUrl, $games, $details = array())
{
	$urls = hlxSitemapUrls($scriptUrl, $games, $details);
	$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
	foreach ($urls as $url) {
		$xml .= "  <url><loc>" . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</loc></url>\n";
	}
	return $xml . "</urlset>\n";
}

function hlxSitemapAbsoluteScriptUrl($scriptUrl, $host, $isHttps)
{
	$scriptUrl = preg_replace('/[?#].*$/', '', trim((string)$scriptUrl));
	if (preg_match('#^https?://[^/]+(?:/.*)?$#i', $scriptUrl)) {
		return $scriptUrl;
	}
	$host = trim((string)$host);
	if (!preg_match('/^(?:[A-Za-z0-9.-]+|\[[0-9A-Fa-f:]+\])(?::[0-9]{1,5})?$/', $host)) {
		return false;
	}
	$path = '/' . ltrim($scriptUrl, '/');
	return ($isHttps ? 'https' : 'http') . '://' . $host . $path;
}
