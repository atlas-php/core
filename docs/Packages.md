# Atlas Core — Package Integration Guide

This document explains how to integrate `atlas-php/core` into other Atlas packages so every project shares the same testing harness, configuration conventions, and service-provider behaviors.

## Testing with `PackageTestCase`

Extend `Atlas\Core\Testing\PackageTestCase` instead of `Orchestra\Testbench\TestCase` in your package tests:

```php
use Atlas\Core\Testing\PackageTestCase;
use Atlas\Relay\Providers\AtlasRelayServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtlasRelayServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(__DIR__.'/../database/migrations');
    }
}
```

Key helpers:

- `loadPackageMigrations($paths)` registers package migrations.
- `runPendingCommand($command, $parameters)` returns a `PendingCommand` **without** auto-running it, so tests can call assertions (`->assertExitCode()`, etc.) safely.
- The base class configures an in-memory sqlite connection named `atlas_core_testbench`. Packages only override database behavior if they truly need to.

## Configurable Models & Table Resolver

Derive Eloquent models from `Atlas\Core\Models\AtlasModel` to automatically read table names and connection overrides from your config file:

```php
use Atlas\Core\Models\AtlasModel;

class Asset extends AtlasModel
{
    protected string $configPrefix = 'atlas-assets';
    protected string $tableKey = 'assets';

    protected function defaultTableName(): string
    {
        return 'atlas_assets';
    }
}
```

Under the hood, this uses `Atlas\Core\Config\TableResolver`, which can also be consumed directly when non-model code needs configurable tables/connections.

## Package Service Providers & Publishing

Every package service provider should extend `Atlas\Core\Providers\PackageServiceProvider`:

```php
use Atlas\Core\Providers\PackageServiceProvider;

final class AtlasAssetsServiceProvider extends PackageServiceProvider
{
    protected string $packageBasePath = __DIR__.'/../..';

    protected function packageSlug(): string
    {
        return 'atlas assets';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->packageConfigPath('atlas-assets.php'), 'atlas-assets');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packageConfigPath('atlas-assets.php') => config_path('atlas-assets.php'),
            ], $this->tags()->config());

            $this->publishes([
                $this->packageDatabasePath('migrations') => database_path('migrations'),
            ], $this->tags()->migrations());

            $this->notifyPendingInstallSteps(
                'Atlas Assets',
                'atlas-assets.php',
                $this->tags()->config(),
                '*atlas_assets*',
                $this->tags()->migrations()
            );
        }
    }
}
```

The base class provides:

- `$packageBasePath`: set this once to point at your package root.
- `packageConfigPath()` / `packageDatabasePath()`: build absolute paths relative to the base path.
- `tags()`: returns a `TagBuilder` derived from your slug, ensuring every package uses the same publish tags (`{slug}-config`, `{slug}-migrations`).
- `notifyPendingInstallSteps()`: emits the standard console reminder when config or migrations haven’t been published.

## Recommendations for New Packages

1. Depend on `atlas-php/core` and configure a `path` repository for local development inside the mono-repo.
2. Extend `AtlasModel` (or `TableResolver`) for any Eloquent models needing configurable tables/connections.
3. Extend `PackageServiceProvider` and set `packageBasePath` + `packageSlug` to inherit all publishing behavior.
4. Extend `PackageTestCase` for your integration/unit tests, using `loadPackageMigrations()` and `runPendingCommand()` as needed.

Following these steps keeps every Atlas package focused on its domain while leveraging the shared infrastructure maintained in Atlas Core.
