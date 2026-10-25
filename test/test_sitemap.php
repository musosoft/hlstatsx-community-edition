<?php
declare(strict_types=1);

define('IN_HLSTATS', true);
require __DIR__ . '/../src/web/includes/sitemap.php';

$scriptUrl = hlxSitemapAbsoluteScriptUrl('/hlstats.php?old=1', 'stats.example', true);
if ($scriptUrl !== 'https://stats.example/hlstats.php') {
	fwrite(STDERR, "Sitemap should construct an absolute URL from a validated host.\n");
	exit(1);
}
if (hlxSitemapAbsoluteScriptUrl('/hlstats.php', 'attacker.example/evil', false) !== false) {
	fwrite(STDERR, "Sitemap should reject malformed host headers.\n");
	exit(1);
}

$xml = hlxSitemapXml($scriptUrl, array(
	array('code' => 'css'),
	array('code' => 'cstrike&evil="<script>'),
));

if (strpos($xml, 'https://stats.example/hlstats.php?mode=players&amp;game=css') === false) {
	fwrite(STDERR, "Sitemap should include game-specific frontend routes.\n");
	exit(1);
}
if (strpos($xml, 'evil') !== false || strpos($xml, '<script>') !== false || substr_count($xml, '<url>') !== 6) {
	fwrite(STDERR, "Sitemap should omit invalid game codes and emit escaped XML.\n");
	exit(1);
}

echo "Sitemap generation checks passed.\n";
