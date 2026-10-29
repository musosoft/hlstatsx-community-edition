<?php

// Small standalone regression suite; no PHPUnit install or application .env needed.
// php tests/admin_accounts.php (SQLite); ADMIN_TEST_MYSQL_HOST=... uses the
// disposable database admin_accounts_test only, never the configured application DB.
// Bootstrap against in-memory SQLite before switching to the explicit test connection.
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\RevalidateAdminAccount;
use App\Models\Admin;
use App\Services\AdminAccountService;
use App\Support\CuratedMigrationPreflight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

$mysql = getenv('ADMIN_TEST_MYSQL_HOST');
if ($mysql && getenv('ADMIN_TEST_MYSQL_ALLOW_RESET') !== '1') {
    throw new RuntimeException('Set ADMIN_TEST_MYSQL_ALLOW_RESET=1 only for a disposable admin_accounts_test database.');
}
config(['database.default' => 'account_test', 'database.connections.account_test' => $mysql ? [
    'driver' => 'mysql', 'host' => $mysql, 'port' => 3306, 'database' => 'admin_accounts_test',
    'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
] : ['driver' => 'sqlite', 'database' => ':memory:']]);
$db = DB::connection()->getPdo();
if ($mysql && $db->query('SELECT DATABASE()')->fetchColumn() !== 'admin_accounts_test') {
    throw new RuntimeException('Refusing to reset any database except admin_accounts_test.');
}
$db->exec('DROP TABLE IF EXISTS hlstats_Admins');
$db->exec('DROP TABLE IF EXISTS hlstats_Users');
$db->exec('CREATE TABLE hlstats_Users (username VARCHAR(16) PRIMARY KEY, password VARCHAR(255), acclevel INT, playerId INT DEFAULT 0)'.($mysql ? ' ENGINE=MyISAM' : ''));
$db->exec('CREATE TABLE hlstats_Admins (adminId INTEGER PRIMARY KEY '.($mysql ? 'AUTO_INCREMENT' : 'AUTOINCREMENT').", username VARCHAR(64) UNIQUE, password VARCHAR(255), accessLevel VARCHAR(16), game VARCHAR(32) DEFAULT '', serverID INT DEFAULT 0, remember_token VARCHAR(100))".($mysql ? ' ENGINE=InnoDB' : ''));
$service = $mysql ? new AdminAccountService : new class($db) extends AdminAccountService {
    public function __construct(private PDO $db) {}
    protected function locked(Closure $operation): mixed { return $operation($this->db); }
};
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (! $condition) { throw new RuntimeException($message); }
    $checks++;
}
function fails(Closure $operation, string $class): void {
    try { $operation(); } catch (Throwable $e) {
        check($e instanceof $class, 'Unexpected exception: '.get_class($e));
        return;
    }
    throw new RuntimeException('Expected '.$class);
}
function insertUser(string $name, string $hash, int $level): void {
    DB::table('hlstats_Users')->insert(['username' => $name, 'password' => $hash, 'acclevel' => $level]);
}
function canonical(string $name): object { return DB::table('hlstats_Users')->where('username', $name)->first(); }
function shadow(string $name): ?object { return DB::table('hlstats_Admins')->where('username', $name)->first(); }
function equalHashes(string $name): bool { return canonical($name)->password === shadow($name)?->password; }
function failWrites(string $table, string $event): void {
    global $db, $mysql;
    $db->exec("CREATE TRIGGER fail_write BEFORE $event ON $table ".($mysql
        ? "FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'injected failure'"
        : "BEGIN SELECT RAISE(FAIL, 'injected failure'); END"));
}
function resumeWrites(): void { DB::connection()->getPdo()->exec('DROP TRIGGER fail_write'); }

$plain = 'correct horse battery';
$bcrypt = password_hash($plain, PASSWORD_BCRYPT);
check(AdminAccountService::passwordMatches($plain, hash('ripemd128', $plain)), 'RIPEMD');
check(AdminAccountService::passwordMatches($plain, $bcrypt), 'bcrypt');
check(! AdminAccountService::passwordMatches('wrong', $bcrypt), 'wrong password');
foreach (['', 'not-a-hash', str_repeat('a', 33)] as $invalid) {
    check(! AdminAccountService::passwordMatches($plain, $invalid), 'invalid format');
}
if (defined('PASSWORD_ARGON2ID')) {
    check(AdminAccountService::passwordMatches($plain, password_hash($plain, PASSWORD_ARGON2ID)), 'Argon2id');
}
check(! AdminAccountService::passwordMatches(str_repeat('a', 73), password_hash(str_repeat('a', 72), PASSWORD_BCRYPT)), 'bcrypt byte alias');
foreach (['short', str_repeat('a', 73), str_repeat('é', 37)] as $invalid) {
    fails(fn () => $service->create('bad', $invalid, 80), ValidationException::class);
}
$service->create('boundary', str_repeat('é', 36), 80);
check(equalHashes('boundary'), '72 byte boundary');
$service->create('admin', $plain, 100);
$service->create('second', $plain, 100);
fails(fn () => $service->delete('admin'), ValidationException::class);
fails(fn () => $service->update('admin', 80), ValidationException::class);
$service->delete('second');
insertUser('only', $bcrypt, 120);
DB::table('hlstats_Users')->where('username', 'admin')->delete(); // fixture: last non-seeded superadmin
fails(fn () => $service->delete('only'), ValidationException::class);
fails(fn () => $service->update('only', 80), ValidationException::class);
check($service->authenticate('admin', $plain) === null, 'orphan shadow rejected');
$service->create('staff', $plain, 80);
DB::table('hlstats_Admins')->where('username', 'staff')->update(['password' => password_hash('old password', PASSWORD_BCRYPT), 'accessLevel' => 'superadmin']);
check($service->authenticate('staff', 'old password') === null, 'stale shadow cannot bypass Users');
$result = $service->authenticate('staff', $plain);
check($result['admin']->accessLevel === 'moderator' && equalHashes('staff'), 'login heals shadow');
$service->update('staff', 0);
check(shadow('staff') === null && $service->authenticate('staff', $plain) === null, 'zero removes access');

insertUser('legacy', hash('ripemd128', $plain), 80);
$challenge = $service->authenticate('legacy', $plain);
check(isset($challenge['proof']) && shadow('legacy') === null, 'legacy requires migration');
failWrites('hlstats_Admins', 'INSERT');
fails(fn () => $service->migratePassword('legacy', $challenge['proof'], $plain), PDOException::class);
check(AdminAccountService::passwordMatches($plain, canonical('legacy')->password), 'migration Users first');
resumeWrites();
$service->migratePassword('legacy', $challenge['proof'], $plain);
check(equalHashes('legacy'), 'migration retry heals');

failWrites('hlstats_Admins', 'INSERT');
fails(fn () => $service->create('partial', $plain, 80), PDOException::class);
check(canonical('partial')->acclevel == 80 && shadow('partial') === null, 'create partial failure');
resumeWrites();
$service->create('partial', $plain, 80);
check(equalHashes('partial'), 'create retry heals');
failWrites('hlstats_Admins', 'UPDATE');
fails(fn () => $service->update('partial', 100, 'new correct password'), PDOException::class);
check($service->authenticate('partial', $plain) === null, 'old shadow rejected after partial password update');
resumeWrites();
$service->authenticate('partial', 'new correct password');
check(equalHashes('partial') && shadow('partial')->accessLevel === 'superadmin', 'login heals failed update');
failWrites('hlstats_Users', 'DELETE');
fails(fn () => $service->delete('partial'), PDOException::class);
check(shadow('partial') === null && canonical('partial')->acclevel == 100, 'delete shadow first');
resumeWrites();
$service->delete('partial');
$service->delete('partial'); // repeated delete succeeds
check(DB::table('hlstats_Users')->where('username', 'partial')->count() === 0, 'delete retry');

$middleware = new RevalidateAdminAccount;
$app->make(\Illuminate\Contracts\Http\Kernel::class); // Register real HTTP middleware groups/aliases.
$route = $app['router']->getRoutes()->getByName('admin.admin-users.index');
$stack = $app['router']->gatherRouteMiddleware($route);
check(array_search(RevalidateAdminAccount::class, $stack, true) !== false
    && array_search(RevalidateAdminAccount::class, $stack, true) < array_search(EnsureSuperAdmin::class, $stack, true), 'role revalidation precedes superadmin checks');
$request = Request::create('/admin');
$request->setLaravelSession($app['session']->driver());
$admin = new Admin(['username' => 'legacy', 'accessLevel' => 'superadmin']);
$request->setUserResolver(fn () => $admin);
$middleware->handle($request, function () use ($admin) {
    check($admin->accessLevel === 'moderator', 'effective role overwrites stale privilege');
    return new Response;
});
fails(fn () => (new EnsureSuperAdmin)->handle($request, fn () => new Response), HttpException::class);
DB::table('hlstats_Users')->where('username', 'legacy')->update(['acclevel' => 100]);
$middleware->handle($request, fn () => new Response);
check($admin->isSuperAdmin(), 'effective role promotion');
foreach ([0, null] as $level) {
    if ($level === null) { DB::table('hlstats_Users')->where('username', 'legacy')->delete(); }
    else { DB::table('hlstats_Users')->where('username', 'legacy')->update(['acclevel' => $level]); }
    fails(fn () => $middleware->handle($request, fn () => new Response), HttpException::class);
}

$preflight = DB::connection();
check(CuratedMigrationPreflight::shouldCheck('migrate'), 'migrate commands receive schema preflight');
check(! CuratedMigrationPreflight::shouldCheck('migrate:status'), 'status remains diagnostic on unsupported schema');
CuratedMigrationPreflight::assertCommandAllowed('migrate:status');
foreach (['migrate:fresh', 'migrate:install', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'] as $command) {
    fails(fn () => CuratedMigrationPreflight::assertCommandAllowed($command), RuntimeException::class);
}
fails(fn () => CuratedMigrationPreflight::assertSupported($preflight), RuntimeException::class);
$preflight->statement('CREATE TABLE hlstats_Options (keyname VARCHAR(64) PRIMARY KEY, value VARCHAR(255))');
$preflight->table('hlstats_Options')->insert(['keyname' => 'dbversion', 'value' => '95']);
fails(fn () => CuratedMigrationPreflight::assertSupported($preflight), RuntimeException::class);
$preflight->table('hlstats_Options')->where('keyname', 'dbversion')->update(['value' => '96']);
CuratedMigrationPreflight::assertSupported($preflight);
check(true, 'official DB96 passes schema preflight');
$preflight->table('hlstats_Options')->where('keyname', 'dbversion')->update(['value' => '97']);
CuratedMigrationPreflight::assertSupported($preflight);
check(true, 'DB97 retries pass schema preflight');
$preflight->statement('DROP TABLE hlstats_Options');

if ($mysql) {
    $other = new PDO("mysql:host=$mysql;dbname=admin_accounts_test", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $name = 'hlstatsx_admin_user_mutation';
    $lock = $other->prepare('SELECT GET_LOCK(?, 0)');
    $lock->execute([$name]);
    check((int) $lock->fetchColumn() === 1, 'fixture lock acquired');
    fails(fn () => $service->create('locked', $plain, 80), HttpException::class);
    check(DB::table('hlstats_Users')->where('username', 'locked')->count() === 0, 'lock contention fails closed');
    $other = null;
}
echo "PASS: $checks checks (".($mysql ? 'MySQL/MariaDB advisory lock + MyISAM/InnoDB' : 'SQLite; advisory lock not exercised').")\n";
