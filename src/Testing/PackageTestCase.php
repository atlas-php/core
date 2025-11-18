<?php

declare(strict_types=1);

namespace Atlas\Core\Testing;

use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RuntimeException;

/**
 * Class PackageTestCase
 *
 * Provides a consistent Testbench harness with sqlite configuration and migration helpers.
 * PRD Reference: Atlas Core Extraction Plan — Shared testing utilities.
 */
abstract class PackageTestCase extends OrchestraTestCase
{
    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $this->configureInMemoryDatabase($app);
    }

    protected function configureInMemoryDatabase(Application $app): void
    {
        $config = $app['config'];
        $config->set('database.default', 'atlas_core_testbench');

        $config->set('database.connections.atlas_core_testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * @param  string|array<int, string>  $paths
     */
    protected function loadPackageMigrations(string|array $paths): void
    {
        foreach ((array) $paths as $path) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Run an artisan command and return the pending command instance for inspection.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function runPendingCommand(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (! $pending instanceof PendingCommand) {
            throw new RuntimeException('Unable to mock console output for the requested command.');
        }

        $pending->run();

        return $pending;
    }
}
