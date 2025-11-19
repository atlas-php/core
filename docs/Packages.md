# Atlas Core — Package Integration Guide

How to integrate **Atlas Core** into any Atlas PHP package.

## Table of Contents
- [Testing with PackageTestCase](#testing-with-packagetestcase)
- [Configurable Models & Table Resolver](#configurable-models--table-resolver)
- [Package Service Providers & Publishing](#package-service-providers--publishing)
- [Recommendations](#recommendations)

## Testing with PackageTestCase
Extend `PackageTestCase`:

```php
abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExampleServiceProvider::class];
    }
}
```

Helpers:
- `loadPackageMigrations()`
- `runPendingCommand()`
- Preconfigured sqlite test connection

## Configurable Models & Table Resolver
Use `AtlasModel`:

```php
final class Asset extends AtlasModel
{
    protected string $configPrefix = 'atlas-assets';
    protected string $tableKey = 'assets';
}
```

`TableResolver` is also available for non-model usage.

## Package Service Providers & Publishing
Extend `PackageServiceProvider`:

```php
final class AtlasAssetsServiceProvider extends PackageServiceProvider
{
    protected string $packageBasePath = __DIR__.'/../..';
}
```

Features:
- Config/migration publishing
- Slug-driven publish tags
- Install reminders
- Path helpers

## Recommendations
1. Require `atlas-php/core` in every package.
2. Use `AtlasModel` for all Eloquent models.
3. Use `PackageServiceProvider` in every package.
4. Use `PackageTestCase` in every test suite.
5. Follow the directory layout in the example package.  
