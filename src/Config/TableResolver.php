<?php

declare(strict_types=1);

namespace Atlas\Core\Config;

use InvalidArgumentException;

/**
 * Class TableResolver
 *
 * Normalizes access to each package's configurable table map and database connection overrides.
 * PRD Reference: Atlas Core Extraction Plan — Shared config helpers.
 */
class TableResolver
{
    private string $prefix;

    public function __construct(string $configPrefix)
    {
        $prefix = trim($configPrefix);

        if ($prefix === '') {
            throw new InvalidArgumentException('The configuration prefix must not be empty.');
        }

        $this->prefix = $prefix;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function key(string $tableKey): string
    {
        return sprintf('%s.tables.%s', $this->prefix(), $tableKey);
    }

    public function resolve(string $tableKey, string $defaultTable): string
    {
        $table = config($this->key($tableKey), $defaultTable);

        if ($table === null || trim((string) $table) === '') {
            return $defaultTable;
        }

        return (string) $table;
    }

    public function connectionKey(): string
    {
        return sprintf('%s.database.connection', $this->prefix());
    }

    public function resolveConnection(?string $defaultConnection = null): ?string
    {
        $connection = config($this->connectionKey(), $defaultConnection);

        if ($connection === null || trim((string) $connection) === '') {
            return $defaultConnection;
        }

        return (string) $connection;
    }
}
