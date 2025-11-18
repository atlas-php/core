# Package Implementation Example

This reference outlines how a typical Atlas package should be structured when it depends on `atlas-php/core`. Use it as a checklist when scaffolding new packages or refactoring existing ones.

## Directory Layout

```
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

- Require `atlas-php/core` alongside Laravel components you need (`illuminate/support`, `illuminate/database`, etc.).
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
    ]
}
```

## Service Provider Pattern

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
        $this->mergeConfigFrom($this->packageConfigPath('example-package.php'), 'example-package');
        $this->app->singleton(Contracts\ExampleServiceInterface::class, Services\ExampleService::class);
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

## Config & Environment Overrides

- Store configurable table names under `example-package.tables.*`.
- Store optional connection overrides under `example-package.database.connection`.
- Provide sensible defaults so the package works without consumer overrides.

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

## Models & Database

- Extend `Atlas\Core\Models\AtlasModel` for any Eloquent model so table names/connections respect config overrides.
- Keep migrations inside `database/migrations` and ensure they read config defaults when creating tables.

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

## Testing

- Base test classes should extend `Atlas\Core\Testing\PackageTestCase`.
- Register your service provider via `getPackageProviders`.
- Load migrations via `loadPackageMigrations` in `defineDatabaseMigrations`.
- Use `runPendingCommand()` when asserting artisan command output/exit codes.

```php
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

## Documentation Expectations

- `README.md` should explain installation, configuration keys, published resources, and usage examples.
- `docs/Install.md` must document the publish/migration workflow, config keys, and any manual setup steps.
- `docs/Full-API.md` enumerates contracts, facades, events, and public services for consumers.
- Additional PRDs or feature briefs also live under `docs/`.
- All classes require PHPDoc blocks referencing the relevant PRD.

## QA Checklist

- `composer lint`
- `composer analyse`
- `composer test`
- `composer dump-autoload`
- Verify publish tags + migrations, confirm config defaults align with the PRD.

Following this template keeps every Atlas package aligned with the conventions defined in `AGENTS.md` while taking full advantage of the shared tooling provided by Atlas Core.
