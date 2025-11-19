<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Testing;

use Illuminate\Testing\PendingCommand;
use Mockery;
use RuntimeException;

/**
 * Class PackageTestCaseTest
 *
 * Verifies PackageTestCase helper utilities receive coverage.
 * PRD Reference: Atlas Core Extraction Plan — Shared testing utilities.
 */
class PackageTestCaseTest extends PackageTestCaseHarness
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_load_package_migrations_accepts_strings_and_arrays(): void
    {
        $this->loadMigrationsThroughHelper('path/to/migrations');
        $this->loadMigrationsThroughHelper(['another/path', 'third/path']);

        $this->assertSame([
            'path/to/migrations',
            'another/path',
            'third/path',
        ], $this->loadedMigrationPaths());
    }

    public function test_run_pending_command_returns_pending_instance(): void
    {
        $pending = Mockery::mock(PendingCommand::class);
        $this->setArtisanHandler(fn () => $pending);

        $this->assertSame(
            $pending,
            $this->runPendingCommandThroughHelper('migrate', ['--force' => true])
        );
    }

    public function test_run_pending_command_throws_when_not_pending(): void
    {
        $this->setArtisanHandler(fn () => null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to mock console output for the requested command.');

        $this->runPendingCommandThroughHelper('list');
    }
}
