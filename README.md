# Atlas Core

Core utilities shared across Atlas PHP packages. This repository ships as a standalone Composer package that can be installed in any Laravel 10/11+ application or package.

---

## Installation

```bash
composer require atlas-php/core
```

After installation the package auto-discovers `Atlasphp\Core\Providers\CoreServiceProvider`, so no manual provider registration is required.

---

## Usage

### Resolve the Core Service

The service provider binds `Atlasphp\Core\Contracts\CoreServiceInterface` as a singleton. Type-hinting the interface in any constructor or invokable class provides access to package metadata:

```php
use Atlasphp\Core\Contracts\CoreServiceInterface;

final class AboutCommand
{
    public function __construct(private CoreServiceInterface $core) {}

    public function handle(): void
    {
        $this->components->info(
            sprintf(
                'Using package %s — %s',
                $this->core->packageName(),
                $this->core->packageDescription(),
            ),
        );
    }
}
```

Typical use cases include:

- Emitting diagnostics in artisan commands or HTTP responses.
- Displaying package build information on health dashboards.
- Providing metadata to downstream logging or observability systems.

### Overriding Metadata

If a consuming project needs to expose custom metadata (for example, a fork or repackaged distribution), simply bind `CoreServiceInterface` to a custom implementation during the application boot process:

```php
use Atlasphp\Core\Contracts\CoreServiceInterface;
use Illuminate\Support\ServiceProvider;

final class CustomCoreMetadataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CoreServiceInterface::class, fn () => new class implements CoreServiceInterface {
            public function packageName(): string
            {
                return 'custom/core-overlay';
            }

            public function packageDescription(): string
            {
                return 'Provides organization-specific defaults.';
            }
        });
    }
}
```

---

## Quality Assurance

| Command            | Purpose                                |
|--------------------|----------------------------------------|
| `composer lint`    | Formats the codebase via Laravel Pint. |
| `composer analyse` | Runs Larastan static analysis.         |
| `composer test`    | Executes PHPUnit test suite.           |

All PRDs and internal contribution standards require these commands to pass before merging changes.

---

## Contributing

See the [Contributing Guide](./CONTRIBUTING.md). All work must align with PRDs and agent workflow rules defined in [AGENTS.md](./AGENTS.md).

---

## License

MIT — see [LICENSE](./LICENSE).
