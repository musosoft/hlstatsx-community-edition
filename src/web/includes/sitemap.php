<?php

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

function hlxSitemapXml($scriptUrl, $games)
{
	$scriptUrl = preg_replace('/[?#].*$/', '', (string)$scriptUrl);
	$urls = array($scriptUrl);
	foreach ($games as $gameRow) {
		$gameCode = (string)$gameRow['code'];
		if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $gameCode)) {
			continue;
		}
		foreach (array('players', 'clans', 'servers', 'maps', 'awards') as $mode) {
			$urls[] = $scriptUrl . '?' . http_build_query(array('mode' => $mode, 'game' => $gameCode), '', '&', PHP_QUERY_RFC3986);
		}
	}

	$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
	foreach ($urls as $url) {
		$xml .= "  <url><loc>" . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
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
