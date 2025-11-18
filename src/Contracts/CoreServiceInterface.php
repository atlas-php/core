<?php

declare(strict_types=1);

namespace Atlasphp\Core\Contracts;

/**
 * Interface CoreServiceInterface
 *
 * Describes the contract for retrieving atlas-php/core package metadata for consuming applications.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
interface CoreServiceInterface
{
    public function packageName(): string;

    public function packageDescription(): string;
}
