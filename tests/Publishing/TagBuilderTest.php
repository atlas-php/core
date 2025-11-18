<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Publishing;

use Atlas\Core\Publishing\TagBuilder;
use Atlas\Core\Tests\TestCase;
use InvalidArgumentException;

/**
 * Class TagBuilderTest
 *
 * Confirms the shared publish tag helper normalizes slugs and suffixes.
 * PRD Reference: Atlas Core Extraction Plan — Shared publishing helpers.
 */
class TagBuilderTest extends TestCase
{
    public function test_generates_known_tag_patterns(): void
    {
        $builder = new TagBuilder('Atlas Assets');

        $this->assertSame('atlas-assets-config', $builder->config());
        $this->assertSame('atlas-assets-migrations', $builder->migrations());
        $this->assertSame('atlas-assets-install', $builder->tag('install'));
    }

    public function test_rejects_empty_arguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TagBuilder('');
    }

    public function test_rejects_empty_suffix(): void
    {
        $builder = new TagBuilder('Atlas Relay');

        $this->expectException(InvalidArgumentException::class);
        $builder->tag('');
    }
}
