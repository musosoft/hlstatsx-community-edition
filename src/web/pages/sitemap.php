<?php
/*
 * HLstatsX: Curated Edition
 * Dynamically generated sitemap for the legacy query-string frontend.
 */

if (!defined('IN_HLSTATS')) {
	die('Do not access this file directly.');
}

header('Content-Type: application/xml; charset=utf-8');
$gameRows = array();
$gameResult = $db->query("SELECT code FROM hlstats_Games WHERE hidden='0' ORDER BY code");
while ($gameRow = $db->fetch_array($gameResult)) {
	$gameRows[] = $gameRow;
}
require_once(INCLUDE_PATH . '/sitemap.php');
$scriptUrl = hlxSitemapAbsoluteScriptUrl(
	$g_options['scripturl'] ?? ($_SERVER['PHP_SELF'] ?? 'hlstats.php'),
	$_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''),
	!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
);
if ($scriptUrl === false) {
	header('HTTP/1.1 500 Internal Server Error', true, 500);
	echo "Sitemap requires a valid site host.\n";
	exit;
}
echo hlxSitemapXml($scriptUrl, $gameRows);
exit;
