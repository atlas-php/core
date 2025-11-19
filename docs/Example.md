# Package Implementation Example

This reference outlines how a typical Atlas package should be structured when it depends on `atlas-php/core`. Use it as a checklist when scaffolding new packages or refactoring existing ones.

## Table of Contents
- [Directory Layout](#directory-layout)
- [Composer Configuration](#composer-configuration)
- [Service Provider Pattern](#service-provider-pattern)
- [Config & Environment Overrides](#config--environment-overrides)
- [Models & Database](#models--database)
- [Testing](#testing)
- [Documentation Expectations](#documentation-expectations)
- [QA Checklist](#qa-checklist)

## Directory Layout

A recommended layout for Atlas packages:

```text
example-package/
├── composer.json
├── docs/
│   ├── Install.md
│   ├── Full-API.md
│   └── (additional PRDs or references)
├── config/
│   └── example-package.php
├── database/
│   ├── factories/
│   └── migrations/
├── routes/
│   └── example-package.php (if applicable)
├── src/
│   ├── Providers/ExamplePackageServiceProvider.php
│   ├── Models/ExampleModel.php
│   ├── Services/ExampleService.php
│   ├── Support/
│   └── Contracts/
├── tests/
│   ├── Feature/
│   └── TestCase.php
└── README.md
```

## Composer Configuration

- Require `atlas-php/core` alongside the Laravel components you need (`illuminate/support`, `illuminate/database`, etc.).
- Include a `path` repository for local development inside the mono-repo so Composer symlinks to `../core` automatically.
- Register your package provider under `extra.laravel.providers` for auto-discovery.

```json
{
    "require": {
        "atlas-php/core": "^<latest-release>",
        "illuminate/support": "^11.0"
    },
    "repositories": [
        {"type": "path", "url": "../core", "options": {"symlink": true}}
    ],
    "extra": {
        "laravel": {
            "providers": [
                "Vendor\\Example\\Providers\\ExamplePackageServiceProvider"
            ]
        }
    }
}
```

## Service Provider Pattern

Packages should extend `Atlas\Core\Providers\PackageServiceProvider` to inherit common behavior:

```php
use Atlas\Core\Providers\PackageServiceProvider;

final class ExamplePackageServiceProvider extends PackageServiceProvider
{
    protected string $packageBasePath = __DIR__.'/../..';

    protected function packageSlug(): string
    {
        return 'example package';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->packageConfigPath('example-package.php'),
            'example-package'
        );

        $this->app->singleton(
            Contracts\ExampleServiceInterface::class,
            Services\ExampleService::class
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packageConfigPath('example-package.php') => config_path('example-package.php'),
            ], $this->tags()->config());

            $this->publishes([
                $this->packageDatabasePath('migrations') => database_path('migrations'),
            ], $this->tags()->migrations());

            $this->notifyPendingInstallSteps(
                'Example Package',
                'example-package.php',
                $this->tags()->config(),
                '*example_package*',
                $this->tags()->migrations()
            );
        }

        $this->loadRoutesFrom($this->packagePath('routes/example-package.php'));
    }
}
```

Key points:

- Use `packageBasePath` once to anchor all paths.
- Use `packageConfigPath()` / `packageDatabasePath()` / `packagePath()` helpers instead of hard-coding paths.
- Use `tags()` for consistent publish tags.
- Use `notifyPendingInstallSteps()` to surface missing publish steps in the console.

## Config & Environment Overrides

Configuration should expose both table names and connection options, with safe defaults:

```php
return [
    'tables' => [
        'records' => 'example_records',
    ],
    'database' => [
        'connection' => env('EXAMPLE_DATABASE_CONNECTION'),
    ],
];
```

Guidelines:

- Store configurable table names under `example-package.tables.*`.
- Store optional connection overrides under `example-package.database.connection`.
- Default values should work out of the box without requiring any environment overrides.

## Models & Database

Models should extend `Atlas\Core\Models\AtlasModel` so they automatically resolve table names and connections from config:

```php
use Atlas\Core\Models\AtlasModel;

final class ExampleRecord extends AtlasModel
{
    protected string $configPrefix = 'example-package';
    protected string $tableKey = 'records';

    protected function defaultTableName(): string
    {
        return 'example_records';
    }
}
```

Migrations should live under `database/migrations` and respect the same config-driven defaults where possible when creating tables.

## Testing

Base test classes should extend `Atlas\Core\Testing\PackageTestCase`:

```php
use Atlas\Core\Testing\PackageTestCase;
use Vendor\Example\Providers\ExamplePackageServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExamplePackageServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(__DIR__.'/../database/migrations');
    }
}
```

Recommended helpers:

- `loadPackageMigrations($paths)` registers package migrations.
- `runPendingCommand($command, $parameters = [])` returns a `PendingCommand` so you can assert exit codes, output, etc.
- The base class configures an in-memory sqlite connection (`atlas_core_testbench`) for most packages.

Packages should only override the database configuration when absolutely necessary.

## Documentation Expectations

Each package should include:

- `README.md` explaining installation, configuration keys, published resources, and basic usage examples.
- `docs/Install.md` describing publish/migration workflow, key config options, and any manual setup.
- `docs/Full-API.md` listing contracts, facades, events, and public services.
- Additional PRDs or feature briefs under `docs/` as needed.
- PHPDoc blocks on public classes and methods that reference the relevant PRD or documentation section.

## QA Checklist

Before tagging a release:

- `composer lint` — code style (Laravel Pint).
- `composer analyse` — static analysis (Larastan).
- `composer test` — test suite.
- `composer dump-autoload` — confirm classmap/autoloading.
- Verify publish tags and migrations behave as expected.
- Confirm config defaults align with the documented PRDs.
