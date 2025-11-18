<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Providers;

use Atlas\Core\Providers\PackageServiceProvider;
use Atlas\Core\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
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
