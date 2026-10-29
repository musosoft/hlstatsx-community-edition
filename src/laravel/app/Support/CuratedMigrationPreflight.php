<?php

namespace App\Support;

use Illuminate\Database\Connection;
use RuntimeException;

final class CuratedMigrationPreflight
{
    private const SUPPORTED_VERSIONS = ['96', '97'];

    public static function shouldCheck(string $command): bool
    {
        return $command === 'migrate';
    }

    public static function assertCommandAllowed(string $command): void
    {
        if (str_starts_with($command, 'migrate:') && $command !== 'migrate:status') {
            throw new RuntimeException('Only forward `migrate` and read-only `migrate:status` are allowed for the legacy schema.');
        }
    }

    public static function assertSupported(Connection $connection): void
    {
        $schema = $connection->getSchemaBuilder();
        if (! $schema->hasTable('hlstats_Options')) {
            throw new RuntimeException('Laravel migrations require the official Curated Edition DB96 schema.');
        }

        $version = $connection->table('hlstats_Options')
            ->where('keyname', 'dbversion')
            ->value('value');

        if (! in_array((string) $version, self::SUPPORTED_VERSIONS, true)) {
            throw new RuntimeException('Laravel migrations require official DB96 input (or a DB97 retry). No DDL was run.');
        }
    }
}
