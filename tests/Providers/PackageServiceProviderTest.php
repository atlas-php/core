<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Providers;

use Atlas\Core\Providers\PackageServiceProvider;
use Atlas\Core\Publishing\TagBuilder;
use Atlas\Core\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class PackageServiceProviderTest
 *
 * Exercises the reusable publishing helpers exposed by PackageServiceProvider.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
class PackageServiceProviderTest extends TestCase
{
    private string $tempBasePath;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem;
        $this->tempBasePath = sys_get_temp_dir().'/atlas-core-tests/'.Str::random(8);

        $this->filesystem->makeDirectory($this->configPath(), 0777, true, true);
        $this->filesystem->makeDirectory($this->migrationsPath(), 0777, true, true);

        $this->app->useConfigPath($this->configPath());
        $this->app->useDatabasePath($this->databasePath());
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->tempBasePath);

        parent::tearDown();
    }

    public function test_notifier_outputs_steps_when_resources_missing(): void
    {
        $provider = new StubPackageServiceProvider($this->app);
        $provider->setSkipNotifications(false);

        $provider->triggerNotification(
            'Atlas Assets',
            'atlas-assets.php',
            'atlas-assets-config',
            '*atlas_assets*',
            'atlas-assets-migrations'
        );

        $output = $provider->output();

        $this->assertStringContainsString('[Atlas Assets]', $output);
        $this->assertStringContainsString('atlas-assets-config', $output);
        $this->assertStringContainsString('atlas-assets-migrations', $output);
        $this->assertStringContainsString('php artisan migrate', $output);
    }

    public function test_notifier_silences_when_everything_published(): void
    {
        file_put_contents($this->configPath().'/atlas-assets.php', '<?php return [];');
        file_put_contents($this->migrationsPath().'/2024_01_01_000000_create_atlas_assets_table.php', '');

        $provider = new StubPackageServiceProvider($this->app);
        $provider->setSkipNotifications(false);

        $provider->triggerNotification(
            'Atlas Assets',
            'atlas-assets.php',
            'atlas-assets-config',
            '*atlas_assets*',
            'atlas-assets-migrations'
        );

        $this->assertSame('', $provider->output());
    }

    public function test_config_and_migration_checks_are_individually_accessible(): void
    {
        $provider = new StubPackageServiceProvider($this->app);

        $this->assertFalse($provider->configPublishedPublic('atlas-config.php'));

        file_put_contents($this->configPath().'/atlas-config.php', '<?php return [];');

        $this->assertTrue($provider->configPublishedPublic('atlas-config.php'));

        $this->assertFalse($provider->migrationsPublishedPublic('*atlas_assets*'));

        $migrationFile = $this->migrationsPath().'/2024_02_01_000000_create_atlas_assets.php';
        file_put_contents($migrationFile, '');

        $this->assertTrue($provider->migrationsPublishedPublic('*atlas_assets*'));
        $this->assertTrue($provider->migrationsPublishedPublic($migrationFile, true));
    }

    public function test_package_path_requires_and_builds_from_base_path(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        $basePath = $provider->basePath();

        $this->assertSame($basePath, $provider->packagePathPublic());
        $this->assertSame($basePath.'/config/app.php', $provider->packagePathPublic('config/app.php'));

        $provider->clearBasePath();

        $this->expectException(LogicException::class);
        $provider->packagePathPublic();
    }

    public function test_package_config_and_database_helpers_reference_package_root(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);

        $this->assertSame(
            $provider->basePath().'/config/atlas.php',
            $provider->packageConfigPathPublic('atlas.php')
        );
        $this->assertSame(
            $provider->basePath().'/database/migrations/table.php',
            $provider->packageDatabasePathPublic('migrations/table.php')
        );
    }

    public function test_tags_are_cached_per_package(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        $tagBuilder = $provider->tagsPublic();

        $this->assertSame('helper-stub', $tagBuilder->slug());
        $this->assertSame($tagBuilder, $provider->tagsPublic());
    }

    public function test_should_skip_install_notification_respects_environment(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);

        $this->assertTrue($provider->shouldSkipInstallNotificationPublic());

        $originalEnv = $this->app['env'];
        $this->app->instance('env', 'production');

        try {
            $this->assertFalse($provider->shouldSkipInstallNotificationPublic());
        } finally {
            $this->app->instance('env', $originalEnv);
        }
    }

    public function test_console_output_uses_container_binding_when_available(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        $boundOutput = new TestConsoleOutput;

        $this->app->instance(ConsoleOutput::class, $boundOutput);

        $this->assertSame($boundOutput, $provider->consoleOutputPublic());

        unset($this->app[ConsoleOutput::class]);
    }

    public function test_console_output_instantiates_default_when_unbound(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        unset($this->app[ConsoleOutput::class]);

        $output = $provider->consoleOutputPublic();

        $this->assertInstanceOf(ConsoleOutput::class, $output);
        $this->assertNotSame($provider->consoleOutputPublic(), $output);
    }

    public function test_config_published_handles_helper_availability(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        $provider->setConfigHelperAvailability(false);

        $this->assertFalse($provider->configPublishedPublic('atlas.php'));

        $provider->setConfigHelperAvailability(true);
        file_put_contents($provider->packageConfigPathPublic('atlas.php'), '<?php return [];');

        $this->assertTrue($provider->configPublishedPublic('atlas.php'));
    }

    public function test_migrations_published_handles_helper_availability(): void
    {
        $provider = new HelperAccessPackageServiceProvider($this->app);
        $provider->setDatabaseHelperAvailability(false);

        $this->assertFalse($provider->migrationsPublishedPublic('*.php'));

        $provider->setDatabaseHelperAvailability(true);
        $migration = $provider->packageDatabasePathPublic('migrations/2024_01_01_000000_create_widgets.php');
        file_put_contents($migration, '');

        $this->assertTrue($provider->migrationsPublishedPublic('*.php'));
        $this->assertTrue($provider->migrationsPublishedPublic($migration, true));
    }

    private function configPath(): string
    {
        return $this->tempBasePath.'/config';
    }

    private function databasePath(): string
    {
        return $this->tempBasePath.'/database';
    }

    private function migrationsPath(): string
    {
        return $this->databasePath().'/migrations';
    }
}

/**
 * Class StubPackageServiceProvider
 *
 * Provides accessors for testing the protected helpers.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
class StubPackageServiceProvider extends PackageServiceProvider
{
    protected string $packageBasePath;

    private ?TestConsoleOutput $output = null;

    private bool $skipNotifications = true;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->packageBasePath = dirname(__DIR__, 2);
    }

    public function setSkipNotifications(bool $skipNotifications): void
    {
        $this->skipNotifications = $skipNotifications;
    }

    public function triggerNotification(
        string $packageName,
        ?string $configFile,
        ?string $configTag,
        ?string $migrationPattern,
        ?string $migrationTag,
        bool $patternIncludesFullPath = false
    ): void {
        $this->notifyPendingInstallSteps(
            $packageName,
            $configFile,
            $configTag,
            $migrationPattern,
            $migrationTag,
            $patternIncludesFullPath
        );
    }

    public function output(): string
    {
        /** @var TestConsoleOutput $console */
        $console = $this->consoleOutput();

        return $console->fetch();
    }

    public function configPublishedPublic(string $configFile): bool
    {
        return $this->configPublished($configFile);
    }

    public function migrationsPublishedPublic(string $pattern, bool $fullPath = false): bool
    {
        return $this->migrationsPublished($pattern, $fullPath);
    }

    protected function consoleOutput(): ConsoleOutput
    {
        if ($this->output === null) {
            $this->output = new TestConsoleOutput;
        }

        return $this->output;
    }

    protected function shouldSkipInstallNotification(): bool
    {
        return $this->skipNotifications;
    }

    protected function packageSlug(): string
    {
        return 'stub package';
    }
}

/**
 * Class TestConsoleOutput
 *
 * Captures console messages in-memory for assertions.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
class TestConsoleOutput extends ConsoleOutput
{
    private string $buffer = '';

    /**
     * @param  string|array<int, string>  $messages
     */
    public function write($messages, bool $newline = false, int $options = 0): void
    {
        foreach ((array) $messages as $message) {
            $this->buffer .= (string) $message;

            if ($newline) {
                $this->buffer .= PHP_EOL;
            }
        }
    }

    public function fetch(): string
    {
        return $this->buffer;
    }
}

/**
 * Class HelperAccessPackageServiceProvider
 *
 * Exposes PackageServiceProvider helpers for targeted coverage.
 * PRD Reference: Atlas Core Extraction Plan — Shared provider behavior.
 */
class HelperAccessPackageServiceProvider extends PackageServiceProvider
{
    protected string $packageBasePath;

    private Filesystem $filesystem;

    private bool $configHelperAvailable = true;

    private bool $databaseHelperAvailable = true;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->filesystem = new Filesystem;
        $this->packageBasePath = rtrim(sys_get_temp_dir().'/atlas-core-provider/'.Str::random(8), DIRECTORY_SEPARATOR);

        $this->filesystem->makeDirectory($this->packageBasePath.'/config', 0777, true, true);
        $this->filesystem->makeDirectory($this->packageBasePath.'/database/migrations', 0777, true, true);
    }

    public function __destruct()
    {
        if ($this->packageBasePath !== '') {
            $this->filesystem->deleteDirectory($this->packageBasePath);
        }
    }

    public function basePath(): string
    {
        return $this->packagePathPublic();
    }

    public function clearBasePath(): void
    {
        $this->packageBasePath = '';
    }

    public function packagePathPublic(string $path = ''): string
    {
        return $this->packagePath($path);
    }

    public function packageConfigPathPublic(string $file): string
    {
        return $this->packageConfigPath($file);
    }

    public function packageDatabasePathPublic(string $path = ''): string
    {
        return $this->packageDatabasePath($path);
    }

    public function tagsPublic(): TagBuilder
    {
        return $this->tags();
    }

    public function shouldSkipInstallNotificationPublic(): bool
    {
        return parent::shouldSkipInstallNotification();
    }

    public function consoleOutputPublic(): ConsoleOutput
    {
        return parent::consoleOutput();
    }

    public function configPublishedPublic(string $configFile): bool
    {
        return $this->configPublished($configFile);
    }

    public function migrationsPublishedPublic(string $pattern, bool $fullPath = false): bool
    {
        return $this->migrationsPublished($pattern, $fullPath);
    }

    public function setConfigHelperAvailability(bool $available): void
    {
        $this->configHelperAvailable = $available;
    }

    public function setDatabaseHelperAvailability(bool $available): void
    {
        $this->databaseHelperAvailable = $available;
    }

    protected function hasConfigPathHelper(): bool
    {
        return $this->configHelperAvailable;
    }

    protected function resolveConfigPath(string $configFile): string
    {
        return $this->packageConfigPath($configFile);
    }

    protected function hasDatabasePathHelper(): bool
    {
        return $this->databaseHelperAvailable;
    }

    protected function resolveDatabasePath(string $path): string
    {
        return $this->packageDatabasePath($path);
    }

    protected function packageSlug(): string
    {
        return 'helper-stub';
    }
}
