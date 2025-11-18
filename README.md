# Atlas Core

Atlas Core provides the common infrastructure all Atlas packages share—testing harnesses, configuration helpers, publishing conventions, and metadata contracts—so every downstream package can focus purely on its domain.

## Install

```bash
composer require atlas-php/core
```

Laravel auto-discovers `Atlas\Core\Providers\CoreServiceProvider`.

## Key Components

- **Testing harness** — `PackageTestCase` standardizes the Testbench environment, migration loading, and artisan helpers. [Learn more ➜](./docs/Packages.md#testing-with-packagetestcase)
- **Configurable models** — `Atlas\Core\Models\AtlasModel` and the `TableResolver` centralize table/connection overrides. [Learn more ➜](./docs/Packages.md#configurable-models--table-resolver)
- **Provider & publish helpers** — `PackageServiceProvider` + `TagBuilder` handle config/migration publishing and install reminders. [Learn more ➜](./docs/Packages.md#package-service-providers--publishing)
- **Metadata contract** — `CoreServiceInterface` exposes package identification that consumers can override when needed.

See the [Package Integration Guide](./docs/Packages.md) for end-to-end usage patterns and recommendations.

## Quality Assurance

| Command            | Purpose                                |
|--------------------|----------------------------------------|
| `composer lint`    | Formats the codebase via Laravel Pint. |
| `composer analyse` | Runs Larastan static analysis.         |
| `composer test`    | Executes PHPUnit test suite.           |

## Contributing

See the [Contributing Guide](./CONTRIBUTING.md). All work must align with PRDs and agent workflow rules defined in [AGENTS.md](./AGENTS.md).

## License

MIT — see [LICENSE](./LICENSE).
