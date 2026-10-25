<?php
declare(strict_types=1);

define('IN_HLSTATS', true);
define('IMAGE_PATH', 'hlstatsimg');
require __DIR__ . '/../src/web/includes/functions.php';

function renderIngameHeader(string $style): string
{
	$_COOKIE = ['style' => $style];
	$_SERVER['HTTP_REFERER'] = '';
	$g_options = ['style' => 'classic.css'];

	ob_start();
	include __DIR__ . '/../src/web/pages/ingame/header.php';
	return (string)ob_get_clean();
}

$dark = renderIngameHeader('curated_dark.css');
if (strpos($dark, 'styles/curated_dark.css') === false || strpos($dark, 'name="viewport"') === false) {
	fwrite(STDERR, "Selected modern theme must reach in-game pages with a mobile viewport.\n");
	exit(1);
}

$invalid = renderIngameHeader('../config.php');
if (strpos($invalid, 'styles/classic.css') === false || strpos($invalid, 'name="viewport"') !== false) {
	fwrite(STDERR, "Invalid style cookie must fall back safely without changing classic viewport behavior.\n");
	exit(1);
}

echo "Theme selection checks passed.\n";
