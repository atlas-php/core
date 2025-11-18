<?php

declare(strict_types=1);

namespace Atlas\Core\Services;

use Atlas\Core\Contracts\CoreServiceInterface;
use InvalidArgumentException;

/**
 * Class CoreService
 *
 * Provides metadata about the atlas-php/core package for consumers that need runtime identification.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
class CoreService implements CoreServiceInterface
{
    public function __construct(
        private readonly string $name = 'atlas-php/core',
        private readonly string $description = 'Core utilities for Atlas PHP Laravel packages.'
    ) {
        $this->guardMetadata($name, $description);
    }

    public function packageName(): string
    {
        return $this->name;
    }

    public function packageDescription(): string
    {
        return $this->description;
    }

    private function guardMetadata(string $name, string $description): void
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('The package name must not be empty.');
        }

        if (trim($description) === '') {
            throw new InvalidArgumentException('The package description must not be empty.');
        }
    }
}
