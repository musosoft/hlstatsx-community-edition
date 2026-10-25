<?php
declare(strict_types=1);

define('IN_HLSTATS', true);

class OpenStreetMapTestDb
{
	private array $rows = [];

	public function escape($value): string
	{
		return addslashes((string)$value);
	}

	public function query(string $sql)
	{
		$this->rows = array();
		if (strpos($sql, 'hlstats_Players') !== false) {
			$this->rows[] = array(
				'playerId' => 7, 'lastName' => '</script><script>alert(1)</script>',
				'country' => 'US', 'kills' => 12, 'deaths' => 3,
				'lat' => '37.7', 'lng' => '-122.4', 'city' => 'San Francisco',
			);
		}
		return true;
	}

	public function fetch_array($result = null)
	{
		return array_shift($this->rows) ?: false;
	}
}

$db = new OpenStreetMapTestDb();
$game = 'css';
$clan = 10;
$clandata = array('mapregion' => 'FRANCE');
$g_options = array('google_map_region' => 'WORLD');
require __DIR__ . '/../src/web/includes/openstreetmap.php';

if (hlxOsmRegionCenter('FRANCE') !== array(47.2, 2.4, 5)) {
	fwrite(STDERR, "Legacy map region preferences must resolve to OSM centers.\n");
	exit(1);
}

ob_start();
hlxPrintOsmMap('clan');
$html = (string)ob_get_clean();

foreach (array('tile.openstreetmap.org/{z}/{x}/{y}.png', 'OpenStreetMap</a> contributors', 'L.circleMarker', 'hlx-osm-map') as $expected) {
	if (strpos($html, $expected) === false) {
		fwrite(STDERR, "OSM map output is missing expected feature: $expected\n");
		exit(1);
	}
}
if (strpos($html, '</script><script>alert(1)</script>') !== false || strpos($html, '\u003C\/script\u003E') === false) {
	fwrite(STDERR, "Database names must be JSON-safe before entering the map script.\n");
	exit(1);
}

echo "OpenStreetMap rendering checks passed.\n";
