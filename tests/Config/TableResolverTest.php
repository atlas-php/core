<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Config;

use Atlas\Core\Config\TableResolver;
use Atlas\Core\Tests\TestCase;
use InvalidArgumentException;

/**
 * Class TableResolverTest
 *
 * Ensures consistent config key generation for Atlas package tables.
 * PRD Reference: Atlas Core Extraction Plan — Shared config helpers.
 */
class TableResolverTest extends TestCase
{
    public function test_resolves_table_names_and_connections(): void
    {
        config()->set('atlas-demo.tables.widgets', 'demo_widgets');
        config()->set('atlas-demo.database.connection', 'atlas_demo');

        $resolver = new TableResolver('atlas-demo');

        $this->assertSame('atlas-demo.tables.widgets', $resolver->key('widgets'));
        $this->assertSame('demo_widgets', $resolver->resolve('widgets', 'fallback'));
        $this->assertSame('atlas_demo', $resolver->resolveConnection());
    }

    public function test_defaults_when_config_missing(): void
    {
        $resolver = new TableResolver('atlas-missing');

        $this->assertSame('fallback_table', $resolver->resolve('missing', 'fallback_table'));
        $this->assertNull($resolver->resolveConnection());
    }

    public function test_rejects_empty_prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new TableResolver('');
    }
}
