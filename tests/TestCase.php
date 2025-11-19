<?php

declare(strict_types=1);

namespace Atlas\Core\Tests;

use Atlas\Core\Testing\PackageTestCase;

/**
 * Class TestCase
 *
 * Provides the base Testbench configuration for exercising the atlas-php/core package.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
abstract class TestCase extends PackageTestCase
{
    // Core does not require auto-loaded providers for its test suite.
}
