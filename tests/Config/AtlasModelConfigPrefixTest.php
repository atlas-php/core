<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Config;

use Atlas\Core\Models\AtlasModel;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Class AtlasModelConfigPrefixTest
 *
 * Validates the config prefix guard on AtlasModel implementations.
 * PRD Reference: Atlas Core Extraction Plan — Shared data abstractions.
 */
class AtlasModelConfigPrefixTest extends TestCase
{
    public function test_config_prefix_must_be_defined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/configuration prefix/');

        new class extends AtlasModel
        {
            protected string $configPrefix = ' ';

            protected string $tableKey = 'widgets';

            protected function defaultTableName(): string
            {
                return 'atlas_widgets';
            }
        };
    }
}
