<?php

declare(strict_types=1);

namespace Atlas\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class PackageServiceProvider
 *
 * Provides reusable console install notifications and publish-state helpers for Atlas packages.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
abstract class PackageServiceProvider extends ServiceProvider
{
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
        if (! function_exists('config_path')) {
            return false;
        }

        return file_exists(config_path($configFile));
    }

    protected function migrationsPublished(string $globPattern, bool $patternIncludesMigrationsDirectory = false): bool
    {
        if (! function_exists('database_path')) {
            return false;
        }

        $pattern = $patternIncludesMigrationsDirectory
            ? $globPattern
            : rtrim(database_path('migrations'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($globPattern, DIRECTORY_SEPARATOR);

        $matches = glob($pattern);

        return $matches !== false && $matches !== [];
    }
}
