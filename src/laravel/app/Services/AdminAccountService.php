<?php

namespace App\Services;

use App\Models\Admin;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDO;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Users is MyISAM: writes cannot roll back. Always reconcile Users -> Admins. */
class AdminAccountService
{
    public static function role(int $level): ?string
    {
        return $level >= 100 ? 'superadmin' : ($level >= 80 ? 'moderator' : null);
    }

    public static function passwordRules(): array
    {
        return ['required', 'string', 'min:12', function ($attribute, $value, $fail) {
            if (strlen($value) > 72) {
                $fail('The password must not exceed 72 bytes.');
            }
        }];
    }

    public static function isLegacy(string $hash): bool
    {
        return strlen($hash) === 32 && ctype_xdigit($hash);
    }

    public static function passwordMatches(string $plain, string $hash): bool
    {
        if (self::isLegacy($hash)) {
            return hash_equals(strtolower($hash), hash('ripemd128', $plain));
        }
        // Supports bcrypt/Argon independent of HASH_DRIVER. No truncation aliases.
        return (password_get_info($hash)['algoName'] !== 'bcrypt' || strlen($plain) <= 72)
            && password_verify($plain, $hash);
    }

    public function authenticate(string $username, string $password): ?array
    {
        return $this->locked(function (PDO $db) use ($username, $password) {
            $user = $this->user($db, $username);
            if (! $user || ! self::role((int) $user->acclevel)
                || ! self::passwordMatches($password, $user->password)) {
                return null;
            }
            if (self::isLegacy($user->password)) {
                return ['username' => $user->username, 'proof' => hash('sha256', $user->password)];
            }
            return ['admin' => $this->mirror($db, $user)];
        });
    }

    public function migratePassword(string $username, string $proof, string $password): Admin
    {
        return $this->locked(function (PDO $db) use ($username, $proof, $password) {
            $user = $this->user($db, $username);
            if (! $user || ! self::role((int) $user->acclevel)) {
                throw ValidationException::withMessages(['password' => 'Account no longer has admin access.']);
            }
            // After a failed mirror, only the new canonical password can heal it.
            if (! self::isLegacy($user->password)) {
                if (! self::passwordMatches($password, $user->password)) {
                    throw ValidationException::withMessages(['password' => 'Please sign in again.']);
                }
                return $this->mirror($db, $user);
            }
            if (! hash_equals(hash('sha256', $user->password), $proof)) {
                throw ValidationException::withMessages(['password' => 'Please sign in again.']);
            }
            $user->password = $this->hashPassword($password);
            $this->execute($db, 'UPDATE hlstats_Users SET password = ? WHERE username = ?', [$user->password, $user->username]);
            return $this->mirror($db, $user);
        });
    }

    public function create(string $username, string $password, int $level): void
    {
        $this->locked(function (PDO $db) use ($username, $password, $level) {
            $this->validateLevel($level);
            $user = $this->user($db, $username);
            if ($user) {
                // Idempotent retry after a successful Users insert and failed mirror.
                if ((int) $user->acclevel !== $level || ! self::passwordMatches($password, $user->password)) {
                    throw ValidationException::withMessages(['username' => 'The username is already in use.']);
                }
            } else {
                $hash = $this->hashPassword($password);
                $this->execute($db, 'INSERT INTO hlstats_Users (username, password, acclevel, playerId) VALUES (?, ?, ?, 0)', [$username, $hash, $level]);
                $user = $this->user($db, $username);
            }
            $this->mirror($db, $user);
        });
    }

    public function update(string $username, int $level, ?string $password = null): void
    {
        $this->locked(function (PDO $db) use ($username, $level, $password) {
            $this->validateLevel($level);
            $user = $this->user($db, $username);
            abort_unless($user, 404);
            $this->protect($db, $user, $level);
            $hash = $password === null ? $user->password : $this->hashPassword($password);
            $this->execute($db, 'UPDATE hlstats_Users SET acclevel = ?, password = ? WHERE username = ?', [$level, $hash, $user->username]);
            $user->acclevel = $level;
            $user->password = $hash;
            $this->mirror($db, $user);
        });
    }

    public function delete(string $username): void
    {
        $this->locked(function (PDO $db) use ($username) {
            $user = $this->user($db, $username);
            if ($user) {
                $this->protect($db, $user, null);
                $username = $user->username;
            } elseif (strcasecmp($username, 'admin') === 0) {
                throw ValidationException::withMessages(['username' => 'The seeded admin identity is protected.']);
            }
            // If the second write fails, retry still sees Users and can complete.
            $this->execute($db, 'DELETE FROM hlstats_Admins WHERE username = ?', [$username]);
            $this->execute($db, 'DELETE FROM hlstats_Users WHERE username = ?', [$username]);
        });
    }

    private function validateLevel(int $level): void
    {
        if (! in_array($level, [0, 80, 100], true)) {
            throw ValidationException::withMessages(['acclevel' => 'Invalid access level.']);
        }
    }

    private function hashPassword(string $password): string
    {
        validator(['password' => $password], ['password' => self::passwordRules()])->validate();
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function protect(PDO $db, object $user, ?int $newLevel): void
    {
        if (($newLevel === null || $newLevel < 100) && (strcasecmp($user->username, 'admin') === 0
            || ((int) $user->acclevel >= 100 && (int) $db->query('SELECT COUNT(*) FROM hlstats_Users WHERE acclevel >= 100')->fetchColumn() <= 1))) {
            throw ValidationException::withMessages(['acclevel' => 'Cannot delete or demote the seeded admin or last superadmin.']);
        }
    }

    private function user(PDO $db, string $username): object|false
    {
        return $this->execute($db, 'SELECT * FROM hlstats_Users WHERE username = ?', [$username])->fetch(PDO::FETCH_OBJ);
    }

    private function mirror(PDO $db, object $user): ?Admin
    {
        $role = self::role((int) $user->acclevel);
        if ($role === null) {
            $this->execute($db, 'DELETE FROM hlstats_Admins WHERE username = ?', [$user->username]);
            return null;
        }
        $existing = $this->execute($db, 'SELECT * FROM hlstats_Admins WHERE username = ?', [$user->username])->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $this->execute($db, 'UPDATE hlstats_Admins SET password = ?, accessLevel = ? WHERE username = ?', [$user->password, $role, $user->username]);
        } else {
            $this->execute($db, 'INSERT INTO hlstats_Admins (username, password, accessLevel) VALUES (?, ?, ?)', [$user->username, $user->password, $role]);
        }
        $row = $this->execute($db, 'SELECT * FROM hlstats_Admins WHERE username = ?', [$user->username])->fetch(PDO::FETCH_ASSOC);
        return (new Admin)->newFromBuilder($row);
    }

    private function execute(PDO $db, string $sql, array $bindings): \PDOStatement
    {
        $statement = $db->prepare($sql);
        $statement->execute($bindings);
        return $statement;
    }

    protected function locked(Closure $operation): mixed
    {
        // A physical PDO prevents Laravel reconnect/retry from continuing writes
        // after losing the connection-scoped advisory lock.
        $db = DB::connection()->getPdo();
        $name = 'hlstatsx_admin_user_mutation';
        $lock = $this->execute($db, 'SELECT GET_LOCK(?, 5)', [$name])->fetchColumn();
        if ((int) $lock !== 1) {
            throw new HttpException(503, 'Admin accounts are busy; please retry.');
        }
        try {
            return $operation($db);
        } finally {
            $released = $this->execute($db, 'SELECT RELEASE_LOCK(?)', [$name])->fetchColumn();
            if ((int) $released !== 1) {
                throw new HttpException(503, 'Admin account lock was lost; please retry.');
            }
        }
    }
}
