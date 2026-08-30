<?php

header('Content-Type: text/plain; charset=utf-8');

$rawGroup = $_GET['groupid'] ?? '';
if (!is_string($rawGroup) || !preg_match('/^[0-9]{1,20}$/', $rawGroup)) {
    http_response_code(400);
    exit;
}

$url = 'https://lamateam.eu/api/steamGroup?groupid=' . $rawGroup;
$response = false;

if (function_exists('curl_init')) {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_MAXFILESIZE => 16384,
        CURLOPT_USERAGENT => 'HLstatsX-CE-clantag/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($response === false || $status < 200 || $status >= 300) {
        $response = false;
    }
}

if ($response === false && ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 6,
            'follow_location' => 1,
            'max_redirects' => 2,
            'header' => "User-Agent: HLstatsX-CE-clantag/1.0\r\n",
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
}

if (!is_string($response) || strlen($response) > 16384 ||
    !preg_match('/"tag"\s*:\s*"([^"]{1,15})"/', $response, $match)) {
    http_response_code(404);
    exit;
}

$tag = $match[1];
$tag = preg_replace('/\s+/u', ' ', trim($tag));
$tag = preg_replace('/[\x00-\x1F\x7F"\\;]/u', '', $tag);
$tag = function_exists('mb_substr') ? mb_substr($tag, 0, 15, 'UTF-8') : substr($tag, 0, 15);
$tag = trim($tag);

if ($tag === '') {
    http_response_code(404);
    exit;
}

echo $tag;
