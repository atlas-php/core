# Atlas Core

Atlas Core centralizes the shared building blocks every Atlas package depends on. It ships as an installable Composer package for Laravel 10/11+ and focuses on three areas:

- **Data layer helpers** — `Atlas\Core\Models\AtlasModel` and `Atlas\Core\Config\TableResolver` keep tables/connection overrides consistent across packages.
- **Provider utilities** — `Atlas\Core\Providers\PackageServiceProvider` and `Atlas\Core\Publishing\TagBuilder` standardize `vendor:publish` tags and install prompts.
- **Developer tooling** — a `PackageTestCase`, `CoreServiceInterface` metadata contract, and orchestration helpers that make testing and diagnostics uniform.

---

## Installation

```bash
composer require atlas-php/core
```

Laravel auto-discovers `Atlas\Core\Providers\CoreServiceProvider`, so no manual registration is required.

---

## Features

### Configurable Atlas Model

Extend `Atlas\Core\Models\AtlasModel` to opt into configuration-driven table names and optional connection overrides:

```php
use Atlas\Core\Models\AtlasModel;

final class Relay extends AtlasModel
{
    protected string $configPrefix = 'atlas-relay'; // maps to config('atlas-relay.*')
    protected string $tableKey = 'relays';          // resolves config('atlas-relay.tables.relays')

    protected function defaultTableName(): string
    {
        return 'atlas_relays';
    }
}
```

AtlasModel pulls table/connection values from `<prefix>.tables.<key>` and `<prefix>.database.connection`, falling back to the defaults you supply. This keeps migrations, factories, and Eloquent models in sync across every package.

### Table Resolver (standalone)

Need the same behavior outside an Eloquent model? Use `Atlas\Core\Config\TableResolver` directly:

```php
$resolver = new TableResolver('atlas-assets');
$table = $resolver->resolve('assets', 'atlas_assets');
$connection = $resolver->resolveConnection();
```

### Unified Publish Tags & Install Prompts

`Atlas\Core\Providers\PackageServiceProvider` adds helpers for console onboarding:

```php
use Atlas\Core\Providers\PackageServiceProvider;
use Atlas\Core\Publishing\TagBuilder;

final class AtlasAssetsServiceProvider extends PackageServiceProvider
{
    private TagBuilder $tags;

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([...], $this->tags()->config());
            $this->publishes([...], $this->tags()->migrations());

            $this->notifyPendingInstallSteps(
                'Atlas Assets',
                'atlas-assets.php',
                $this->tags()->config(),
                '*atlas_assets*',
                $this->tags()->migrations()
            );
        }
    }

    private function tags(): TagBuilder
    {
        return $this->tags ??= new TagBuilder('atlas assets');
    }
}
```

This guarantees every package emits the same vendor:publish tags and console reminders.

### Package Test Case

`Atlas\Core\Testing\PackageTestCase` bootstraps an in-memory sqlite connection and ships helpers for loading migrations or running artisan commands from Testbench, so every package test suite starts from the same baseline.

### Core Metadata Contract

`Atlas\Core\Contracts\CoreServiceInterface` exposes the package name/description for health checks and diagnostics. Each consuming package can override the binding to describe itself while keeping the API consistent.

---

## Quality Assurance

| Command            | Purpose                                |
|--------------------|----------------------------------------|
| `composer lint`    | Formats the codebase via Laravel Pint. |
| `composer analyse` | Runs Larastan static analysis.         |
| `composer test`    | Executes PHPUnit test suite.           |

---

## Contributing

See the [Contributing Guide](./CONTRIBUTING.md). All work must align with PRDs and agent workflow rules defined in [AGENTS.md](./AGENTS.md).

---

## License

MIT — see [LICENSE](./LICENSE).
