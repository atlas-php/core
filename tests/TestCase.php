<?php

declare(strict_types=1);

namespace Atlas\Core\Tests;

use Atlas\Core\Providers\CoreServiceProvider;
use Atlas\Core\Testing\PackageTestCase;

/**
 * Class TestCase
 *
 * Provides the base Testbench configuration for exercising the atlas-php/core package.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }
}
