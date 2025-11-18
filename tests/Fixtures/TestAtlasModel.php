<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Fixtures;

use Atlas\Core\Models\AtlasModel;

/**
 * Class TestAtlasModel
 *
 * Test fixture model that reads its configuration from the atlas-testing namespace.
 * PRD Reference: Atlas Core Extraction Plan — Shared data abstractions.
 */
class TestAtlasModel extends AtlasModel
{
    protected string $configPrefix = 'atlas-testing';

    protected string $tableKey = 'widgets';

    protected function defaultTableName(): string
    {
        return 'atlas_widgets';
    }
}
