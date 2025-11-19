<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Testing;

use Atlas\Core\Testing\PackageTestCase;
use Illuminate\Testing\PendingCommand;

/**
 * Class PackageTestCaseHarness
 *
 * Exposes protected helpers from PackageTestCase for isolated coverage tests.
 * PRD Reference: Atlas Core Extraction Plan — Shared testing utilities.
 */
class PackageTestCaseHarness extends PackageTestCase
{
    /**
     * @var array<int, string>
     */
    private array $loadedMigrationPaths = [];

    /**
     * @var callable|null
     */
    private $artisanHandler = null;

    protected function defineDatabaseMigrations(): void
    {
        // Harness does not register migrations automatically; tests drive the helper manually.
    }

    protected function loadMigrationsFrom(array|string $paths): void
    {
        foreach ((array) $paths as $path) {
            $this->loadedMigrationPaths[] = $path;
        }
    }

    /**
     * @param  array<int, string>|string  $paths
     */
    public function loadMigrationsThroughHelper(string|array $paths): void
    {
        $this->loadPackageMigrations($paths);
    }

    /**
     * @return array<int, string>
     */
    public function loadedMigrationPaths(): array
    {
        return $this->loadedMigrationPaths;
    }

    public function setArtisanHandler(?callable $handler): void
    {
        $this->artisanHandler = $handler;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function artisan($command, $parameters = [])
    {
        if ($this->artisanHandler !== null) {
            return ($this->artisanHandler)($command, $parameters);
        }

        return parent::artisan($command, $parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function runPendingCommandThroughHelper(string $command, array $parameters = []): PendingCommand
    {
        return $this->runPendingCommand($command, $parameters);
    }
}
