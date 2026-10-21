<?php
// Standalone: php8.5 test/test_php85_compatibility.php
// Uses no configuration files, database, remote master server, or TCP/UDP port.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function check_compatibility($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

define('IN_HLSTATS', true);
define('IMAGE_PATH', '/test-only-images');
$auth = (object) array('userdata' => array('acclevel' => 80));
$task = (object) array('title' => 'Synchronization compatibility fixture');
$_POST = array();
$db = new class {
    public function query($query)
    {
        throw new RuntimeException('Rendering the form must not access a database');
    }
};
ob_start();
require __DIR__ . '/../src/web/pages/admintasks/tools_synchronize.php';
$html = ob_get_clean();
check_compatibility(strpos($html, '<form method="POST">') !== false, 'Form did not render');
check_compatibility(substr_count($html, '<OPTION VALUE=') === 2, 'Master-server choices changed');
check_compatibility(strpos($html, 'name="confirm" value="1"') !== false, 'Confirmation field missing');

// PHP's sockets API declares output parameters by reference. Call sites must
// pass ordinary variables, not the call-time reference syntax removed in PHP 5.4.
$pair = array();
check_compatibility(socket_create_pair(AF_UNIX, SOCK_DGRAM, 0, $pair), 'Cannot create local socket fixture');
try {
    $packet = 'fixture-only';
    check_compatibility(socket_send($pair[0], $packet, strlen($packet), 0) === strlen($packet), 'Fixture send failed');
    $read = array($pair[1]);
    $write = null;
    $except = null;
    $timeout = 1;
    check_compatibility(socket_select($read, $write, $except, $timeout) === 1, 'Fixture select failed');
    $buffer = '';
    $host = '';
    $port = 0;
    check_compatibility(socket_recvfrom($pair[1], $buffer, 2000, 0, $host, $port) === strlen($packet), 'Fixture receive failed');
    check_compatibility($buffer === $packet, 'Output-reference semantics changed');
} finally {
    socket_close($pair[0]);
    socket_close($pair[1]);
}

// utf8_encode() is deprecated. Its input is Latin-1, not UTF-8; preserve
// every byte's mapping, including the C1 range (not Windows-1252).
$latin1 = '';
$expected = '';
for ($byte = 0; $byte < 256; ++$byte) {
    $latin1 .= chr($byte);
    $expected .= $byte < 128
        ? chr($byte)
        : chr(0xc0 | ($byte >> 6)) . chr(0x80 | ($byte & 0x3f));
}
check_compatibility(iconv('ISO-8859-1', 'UTF-8', $latin1) === $expected, 'Latin-1 conversion changed');
check_compatibility(iconv('ISO-8859-1', 'UTF-8', '') === '', 'Empty topic conversion changed');
$teamspeak = file_get_contents(__DIR__ . '/../src/web/pages/teamspeak_query.php');
check_compatibility(strpos($teamspeak, "iconv('ISO-8859-1', 'UTF-8', removeChar(\$topic))") !== false, 'TeamSpeak must use the tested conversion');
restore_error_handler();
echo "PHP compatibility fixtures passed\n";
