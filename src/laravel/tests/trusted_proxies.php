<?php

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Middleware\ForceCanonicalHttps;

putenv('TRUSTED_PROXIES=203.0.113.10');
putenv('APP_URL=https://stats.example.test/laravel');
$_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = '203.0.113.10';
$_ENV['APP_URL'] = $_SERVER['APP_URL'] = 'https://stats.example.test/laravel';
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$httpKernel->bootstrap();

$middleware = $app->make(TrustProxies::class);

$trusted = Request::create('http://stats.example.test/admin', 'GET', [], [], [], [
    'REMOTE_ADDR' => '203.0.113.10',
    'HTTP_X_FORWARDED_PROTO' => 'https',
    'HTTP_X_FORWARDED_PORT' => '443',
]);
$middleware->handle($trusted, static fn (Request $request) => null);
if (! $trusted->isSecure() || $trusted->getSchemeAndHttpHost() !== 'https://stats.example.test') {
    throw new RuntimeException('Trusted HTTPS proxy headers were not applied.');
}

$untrusted = Request::create('http://stats.example.test/admin', 'GET', [], [], [], [
    'REMOTE_ADDR' => '198.51.100.25',
    'HTTP_X_FORWARDED_PROTO' => 'https',
    'HTTP_X_FORWARDED_HOST' => 'attacker.example.test',
    'HTTP_X_FORWARDED_PORT' => '443',
]);
$middleware->handle($untrusted, static fn (Request $request) => null);
if ($untrusted->isSecure() || $untrusted->getSchemeAndHttpHost() !== 'http://stats.example.test') {
    throw new RuntimeException('Untrusted forwarded headers changed the request origin.');
}

$app->instance('request', $untrusted);
URL::setRequest($untrusted);
(new ForceCanonicalHttps)->handle($untrusted, static fn (Request $request) => new \Symfony\Component\HttpFoundation\Response());
if (! str_starts_with(url('/admin/login'), 'https://stats.example.test/')) {
    throw new RuntimeException('Canonical HTTPS APP_URL did not force secure generated URLs.');
}

echo "PASS: trusted proxy HTTPS recognized; untrusted forwarded headers ignored; generated URLs remain HTTPS\n";
