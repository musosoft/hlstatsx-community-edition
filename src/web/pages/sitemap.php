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

$detailSpecs = array(
	array('mode' => 'playerinfo', 'sql' => "SELECT playerId AS value, game FROM hlstats_Players WHERE hideranking=0 AND game IN (SELECT code FROM hlstats_Games WHERE hidden='0') ORDER BY playerId DESC LIMIT 3000"),
	array('mode' => 'claninfo', 'sql' => "SELECT clanId AS value, game FROM hlstats_Clans WHERE hidden=0 AND game IN (SELECT code FROM hlstats_Games WHERE hidden='0') ORDER BY clanId DESC LIMIT 1000"),
	array('mode' => 'servers', 'sql' => "SELECT serverId AS value, game FROM hlstats_Servers WHERE game IN (SELECT code FROM hlstats_Games WHERE hidden='0') ORDER BY serverId DESC LIMIT 1000"),
	array('mode' => 'weaponinfo', 'sql' => "SELECT code AS value, game FROM hlstats_Weapons WHERE code!='' AND game IN (SELECT code FROM hlstats_Games WHERE hidden='0') ORDER BY weaponId DESC LIMIT 1000"),
	array('mode' => 'mapinfo', 'sql' => "SELECT map AS value, game FROM hlstats_Maps_Counts WHERE map!='' AND kills>0 AND game IN (SELECT code FROM hlstats_Games WHERE hidden='0') ORDER BY rowId DESC LIMIT 1000"),
);
$detailRows = array();
foreach ($detailSpecs as $spec) {
	$result = $db->query($spec['sql']);
	while ($row = $db->fetch_array($result)) {
		$detailRows[] = array('mode' => $spec['mode'], 'value' => $row['value'], 'game' => $row['game']);
	}
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
echo hlxSitemapXml($scriptUrl, $gameRows, $detailRows);
exit;
