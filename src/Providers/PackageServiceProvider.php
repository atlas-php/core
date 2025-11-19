<?php

declare(strict_types=1);

namespace Atlas\Core\Providers;

use Atlas\Core\Publishing\TagBuilder;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class PackageServiceProvider
 *
 * Provides reusable console install notifications and publish-state helpers for Atlas packages.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
abstract class PackageServiceProvider extends ServiceProvider
{
    protected string $packageBasePath = '';

    private ?TagBuilder $packageTags = null;

    protected function notifyPendingInstallSteps(
        string $packageName,
        ?string $configFile = null,
        ?string $configTag = null,
        ?string $migrationPattern = null,
        ?string $migrationTag = null,
        bool $migrationPatternIncludesFullPath = false
    ): void {
        if ($this->shouldSkipInstallNotification()) {
            return;
        }

        $missingConfig = $configFile ? ! $this->configPublished($configFile) : false;
        $missingMigrations = $migrationPattern
            ? ! $this->migrationsPublished($migrationPattern, $migrationPatternIncludesFullPath)
            : false;

        if (! $missingConfig && ! $missingMigrations) {
            return;
        }

        $output = $this->consoleOutput();
        $output->writeln('');
        $output->writeln(sprintf('<comment>[%s]</comment> Publish configuration and migrations, then run migrations:', $packageName));

        if ($missingConfig) {
            $output->writeln(sprintf(
                '  %s',
                $configTag
                    ? sprintf('php artisan vendor:publish --tag=%s', $configTag)
                    : sprintf('Publish %s configuration file', $configFile)
            ));
        }

        if ($missingMigrations) {
            $output->writeln(sprintf(
                '  %s',
                $migrationTag
                    ? sprintf('php artisan vendor:publish --tag=%s', $migrationTag)
                    : 'Publish package migrations'
            ));
        }

        $output->writeln('  php artisan migrate');
        $output->writeln('');
    }

    protected function packagePath(string $path = ''): string
    {
        if ($this->packageBasePath === '') {
            throw new LogicException(sprintf('Package base path must be defined on %s.', static::class));
        }

        $base = rtrim($this->packageBasePath, DIRECTORY_SEPARATOR);

        if ($path === '') {
            return $base;
        }

        return $base.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }

    protected function packageConfigPath(string $file): string
    {
        return $this->packagePath('config/'.$file);
    }

    protected function packageDatabasePath(string $path = ''): string
    {
        return $this->packagePath('database'.($path !== '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }

    protected function tags(): TagBuilder
    {
        return $this->packageTags ??= new TagBuilder($this->packageSlug());
    }

    protected function shouldSkipInstallNotification(): bool
    {
        return $this->app->runningUnitTests();
    }

    protected function consoleOutput(): ConsoleOutput
    {
        if ($this->app->bound(ConsoleOutput::class)) {
            return $this->app->make(ConsoleOutput::class);
        }

        return new ConsoleOutput;
    }

    protected function configPublished(string $configFile): bool
    {
        if (! $this->hasConfigPathHelper()) {
            return false;
        }

        return file_exists($this->resolveConfigPath($configFile));
    }

    protected function migrationsPublished(string $globPattern, bool $patternIncludesMigrationsDirectory = false): bool
    {
        if (! $this->hasDatabasePathHelper()) {
            return false;
        }

        $pattern = $patternIncludesMigrationsDirectory
            ? $globPattern
            : rtrim($this->resolveDatabasePath('migrations'), DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .ltrim($globPattern, DIRECTORY_SEPARATOR);

        $matches = glob($pattern);

        return $matches !== false && $matches !== [];
    }

    protected function hasConfigPathHelper(): bool
    {
        return function_exists('config_path');
    }

    protected function resolveConfigPath(string $configFile): string
    {
        return config_path($configFile);
    }

    protected function hasDatabasePathHelper(): bool
    {
        return function_exists('database_path');
    }

    protected function resolveDatabasePath(string $path): string
    {
        return database_path($path);
    }

    abstract protected function packageSlug(): string;
}
