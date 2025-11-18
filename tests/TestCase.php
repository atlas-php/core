<?php

declare(strict_types=1);

namespace Atlasphp\Core\Tests;

use Atlasphp\Core\Providers\CoreServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Class TestCase
 *
 * Provides the base Testbench configuration for exercising the atlas-php/core package.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }
}
