<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Models;

use Atlas\Core\Tests\Fixtures\TestAtlasModel;
use Atlas\Core\Tests\TestCase;
use LogicException;

/**
 * Class AtlasModelTest
 *
 * Validates the configurable table name and connection handling offered by AtlasModel.
 * PRD Reference: Atlas Core Extraction Plan — Shared data abstractions.
 */
class AtlasModelTest extends TestCase
{
    public function test_model_applies_configured_table_and_connection(): void
    {
        config()->set('atlas-testing.tables.widgets', 'custom_widgets');
        config()->set('atlas-testing.database.connection', 'atlas_connection');

        $model = new TestAtlasModel;

        $this->assertSame('custom_widgets', $model->getTable());
        $this->assertSame('atlas_connection', $model->getConnectionName());
    }

    public function test_model_defaults_when_config_missing(): void
    {
        config()->set('atlas-testing.tables.widgets', null);
        config()->set('atlas-testing.database.connection', null);

        $model = new TestAtlasModel;

        $this->assertSame('atlas_widgets', $model->getTable());
        $this->assertNull($model->getConnectionName());
    }

    public function test_model_requires_table_key_definition(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must define a table key');

        new class extends TestAtlasModel
        {
            protected string $tableKey = '';
        };
    }
}
