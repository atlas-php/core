<?php

declare(strict_types=1);

namespace Atlas\Core\Providers;

use Atlas\Core\Contracts\CoreServiceInterface;
use Atlas\Core\Services\CoreService;
use Illuminate\Support\ServiceProvider;

/**
 * Class CoreServiceProvider
 *
 * Registers bindings exposed by the atlas-php/core package for Laravel applications.
 * PRD Reference: Package bootstrap instructions provided in the core package task brief.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CoreServiceInterface::class, static fn (): CoreServiceInterface => new CoreService);
    }

    public function boot(): void
    {
        // No publishable assets yet; reserved for future configuration hooks.
    }
}
