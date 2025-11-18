<?php

declare(strict_types=1);

namespace Atlas\Core\Tests;

use Atlas\Core\Contracts\CoreServiceInterface;
use Atlas\Core\Services\CoreService;
use InvalidArgumentException;

/**
 * Class CoreServiceTest
 *
 * Validates the container bindings and safeguards provided by the CoreService implementation.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
class CoreServiceTest extends TestCase
{
    public function test_core_service_resolves_from_container(): void
    {
        $service = $this->app->make(CoreServiceInterface::class);

        $this->assertSame('atlas-php/core', $service->packageName());
        $this->assertSame('Core utilities for Atlas PHP Laravel packages.', $service->packageDescription());
    }

    public function test_service_is_registered_as_singleton(): void
    {
        $first = $this->app->make(CoreServiceInterface::class);
        $second = $this->app->make(CoreServiceInterface::class);

        $this->assertSame($first, $second);
    }

    public function test_core_service_rejects_empty_metadata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The package name must not be empty.');

        new CoreService('');
    }
}
