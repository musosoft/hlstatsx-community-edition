<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernelContract::class)->bootstrap();

$xml = view('seo.sitemap', [
    'entries' => [[
        'loc' => 'https://example.test/',
        'lastmod' => '2026-10-28',
        'changefreq' => 'daily',
        'priority' => '1.0',
    ]],
])->render();

if (!str_starts_with(ltrim($xml), '<?xml version="1.0" encoding="UTF-8"?>')
    || !str_contains($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
    || !str_contains($xml, '<loc>https://example.test/</loc>')
    || str_contains($xml, '{!!')) {
    throw new RuntimeException('Sitemap Blade view did not render a valid XML document.');
}

echo "PASS: sitemap view renders XML\n";
