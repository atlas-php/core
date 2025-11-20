# Atlas Core

[![Build](https://github.com/atlas-php/core/actions/workflows/tests.yml/badge.svg)](https://github.com/atlas-php/core/actions/workflows/tests.yml)
[![coverage](https://codecov.io/github/atlas-php/core/branch/main/graph/badge.svg)](https://codecov.io/github/atlas-php/core)
[![License](https://img.shields.io/github/license/atlas-php/core.svg)](LICENSE)

**Atlas Core** provides the shared foundation for all Atlas PHP packages — testing harnesses, configuration helpers, and publishing conventions. It standardizes behaviors so each package can stay small, predictable, and focused on its domain.

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
- [Key Components](#key-components)
- [Quality Assurance](#quality-assurance)
- [Also See](#also-see)
- [Contributing](#contributing)
- [License](#license)

## Overview
Atlas Core centralizes package infrastructure: consistent testing, configurable models, publish pipelines, and provider conventions. Every Atlas package builds on this shared layer.

## Installation
```bash
composer require atlas-php/core
```

## Key Components

### Testing Harness
`PackageTestCase` standardizes Testbench usage, migration setup, sqlite defaults, and artisan helpers.

### Configurable Models
`AtlasModel` + `TableResolver` allow all packages to override tables and connections via config.

### Model Services
`Atlas\Core\Services\ModelService` offers shared CRUD helpers for Eloquent-backed services while allowing packages to extend/override query behavior. See [Shared Model Service](./docs/Packages.md#shared-model-service).

### Publishing & Providers
`PackageServiceProvider` and `TagBuilder` give every package consistent config/migration publishing and install reminders.

## Quality Assurance

| Command            | Purpose                                |
|--------------------|----------------------------------------|
| `composer lint`    | Format code with Laravel Pint.         |
| `composer analyse` | Run Larastan static analysis.          |
| `composer test`    | Execute PHPUnit test suite.            |

## Also See
- [Package Integration Guide](./docs/Packages.md)
- [Package Implementation Example](./docs/Example.md)

## Contributing
See the [Contributing Guide](.github/CONTRIBUTING.md).

## License
MIT — see [LICENSE](./LICENSE).
