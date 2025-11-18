<?php

declare(strict_types=1);

namespace Atlas\Core\Publishing;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class TagBuilder
 *
 * Generates consistent vendor:publish tags for every Atlas package.
 * PRD Reference: Atlas Core Extraction Plan — Shared publishing helpers.
 */
class TagBuilder
{
    private string $slug;

    public function __construct(string $packageSlug)
    {
        $slug = Str::slug($packageSlug);

        if ($slug === '') {
            throw new InvalidArgumentException('The package slug must not be empty.');
        }

        $this->slug = $slug;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function config(): string
    {
        return $this->tag('config');
    }

    public function migrations(): string
    {
        return $this->tag('migrations');
    }

    public function tag(string $suffix): string
    {
        $normalizedSuffix = Str::slug($suffix);

        if ($normalizedSuffix === '') {
            throw new InvalidArgumentException('The tag suffix must not be empty.');
        }

        return sprintf('%s-%s', $this->slug(), $normalizedSuffix);
    }
}
