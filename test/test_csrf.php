<?php
define('IN_HLSTATS', true);
$_SESSION = array();
require __DIR__ . '/../src/web/includes/functions.php';

function expect(bool $condition, string $message): void
{
	if (!$condition) {
		fwrite(STDERR, $message . "\n");
		exit(1);
	}
}

$token = hlxCsrfToken();
expect((bool)preg_match('/^[a-f0-9]{64}$/', $token), 'CSRF tokens must be 256-bit hex strings');
expect(hlxCheckCsrf($token), 'the matching token should validate');
expect(!hlxCheckCsrf(''), 'an empty token must fail');
expect(!hlxCheckCsrf(str_repeat('0', 64)), 'a different token must fail');
expect(strpos(hlxCsrfField(), 'name="csrf_token"') !== false, 'form helper must emit the expected field');
expect(valid_game_code('cs2') === 'cs2', 'ordinary game codes must be accepted');
expect(valid_game_code('left-4-dead.2') === 'left-4-dead.2', 'supported punctuation in identifiers must be accepted');
expect(valid_game_code("css' OR 1=1 --") === '', 'SQL syntax must not be accepted as a game code');
expect(valid_game_code(array('css')) === '', 'array request values must not be accepted as game codes');
expect(hlxResolveStylesheet('curated_light.css', 'classic.css') === 'curated_light.css', 'existing curated themes must be selectable');
expect(hlxResolveStylesheet('../config.php', 'classic.css') === 'classic.css', 'stylesheet path traversal must resolve to the default');
expect(hlxResolveStylesheet(array('curated_dark.css'), 'classic.css') === 'classic.css', 'array stylesheet values must resolve to the default');
$legacyPassword = 'legacy-password';
$legacyHash = hash('ripemd128', $legacyPassword);
$upgradedHash = hlxVerifyAdminPassword($legacyPassword, $legacyHash);
expect(is_string($upgradedHash) && $upgradedHash !== $legacyHash && password_verify($legacyPassword, $upgradedHash), 'legacy admin passwords must upgrade to a modern verifiable hash');
$modernHash = hlxAdminPasswordHash('correct horse battery staple');
expect(is_string($modernHash) && password_verify('correct horse battery staple', $modernHash), 'new admin passwords must use a verifiable password hash');
expect(hlxVerifyAdminPassword('wrong password', $modernHash) === false, 'wrong modern password must fail verification');
if (PASSWORD_DEFAULT === PASSWORD_BCRYPT) {
	expect(hlxAdminPasswordHash(str_repeat('x', 73)) === false, 'bcrypt-sized input must not be silently truncated');
}

$_SESSION['csrf_token'] = array('not', 'a', 'string');
$rotated = hlxCsrfToken();
expect(is_string($rotated) && hlxCheckCsrf($rotated), 'invalid session token state must be replaced');

echo "CSRF token checks passed.\n";
