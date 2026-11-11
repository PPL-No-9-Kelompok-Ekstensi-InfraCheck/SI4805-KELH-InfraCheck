<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\TestCase as ApplicationTestCase;

class TestingDatabaseSafetyTest extends TestCase
{
    public function test_safe_configuration_reaches_refresh_with_only_the_memory_connection_available(): void
    {
        $app = $this->makeApplication();
        $probe = $this->makeProbe();

        $probe->bootTraits($app);

        $this->assertTrue($probe->refreshWasCalled);
        $this->assertSame(['sqlite'], array_keys($app['config']->get('database.connections')));
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_unsafe_effective_configuration_is_rejected_before_refresh(array $overrides, array $connections = [null]): void
    {
        $app = $this->makeApplication($overrides);
        $probe = $this->makeProbe();
        $probe->connectionsToTransact = $connections;

        try {
            $probe->bootTraits($app);
            $this->fail('The unsafe configuration was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Unsafe testing database configuration', $exception->getMessage());
            $this->assertFalse($probe->refreshWasCalled);
        }
    }

    public static function unsafeConfigurations(): array
    {
        return [
            'non-testing config' => [['app.env' => 'local']],
            'mysql default' => [['database.default' => 'mysql']],
            'mysql driver under sqlite name' => [['database.connections.sqlite.driver' => 'mysql']],
            'sqlite file' => [['database.connections.sqlite.database' => 'must-not-be-created.sqlite']],
            'connection URL' => [['database.connections.sqlite.url' => 'sqlite:///must-not-be-created.sqlite']],
            'foreign keys disabled' => [['database.connections.sqlite.foreign_key_constraints' => false]],
            'read override' => [['database.connections.sqlite.read' => ['database' => 'must-not-be-created.sqlite']]],
            'write override' => [['database.connections.sqlite.write' => ['driver' => 'mysql']]],
            'extra transaction connection' => [[], ['sqlite', 'mysql']],
            'mysql transaction connection' => [[], ['mysql']],
            'empty transaction list' => [[], []],
        ];
    }

    public function test_non_testing_application_environment_is_rejected_before_refresh(): void
    {
        $app = $this->makeApplication();
        $app->detectEnvironment(fn () => 'production');
        $probe = $this->makeProbe();

        try {
            $probe->bootTraits($app);
            $this->fail('The non-testing application was accepted.');
        } catch (RuntimeException) {
            $this->assertFalse($probe->refreshWasCalled);
        }
    }

    public function test_unsafe_cached_configuration_is_rejected_before_refresh(): void
    {
        $cache = tempnam(sys_get_temp_dir(), 'infracheck-config-');
        $app = new class extends Application
        {
            public string $testConfigCache;

            public function getCachedConfigPath(): string
            {
                return $this->testConfigCache;
            }
        };
        $app->testConfigCache = $cache;
        $config = $this->makeApplication(['database.default' => 'mysql'])['config']->all();
        file_put_contents($cache, '<?php return '.var_export($config, true).';');
        $probe = $this->makeProbe();

        try {
            (new LoadConfiguration)->bootstrap($app);
            $this->assertTrue($app['config_loaded_from_cache']);

            try {
                $probe->bootTraits($app);
                $this->fail('The unsafe cached configuration was accepted.');
            } catch (RuntimeException) {
                $this->assertFalse($probe->refreshWasCalled);
            }
        } finally {
            unlink($cache);
        }
    }

    private function makeApplication(array $overrides = []): Application
    {
        $app = new Application;
        $config = new Repository([
            'app' => ['env' => 'testing', 'timezone' => 'UTC'],
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                        'url' => null,
                        'foreign_key_constraints' => true,
                    ],
                    'mysql' => ['driver' => 'mysql'],
                ],
            ],
        ]);
        $config->set($overrides);
        $app->instance('config', $config);
        $app->detectEnvironment(fn () => 'testing');

        return $app;
    }

    private function makeProbe(): ApplicationTestCase
    {
        return new class('databaseSafetyProbe') extends ApplicationTestCase
        {
            use RefreshDatabase;

            public bool $refreshWasCalled = false;

            public array $connectionsToTransact = [null];

            public function bootTraits(Application $app): void
            {
                $this->app = $app;
                $this->setUpTraits();
            }

            public function refreshDatabase(): void
            {
                $this->refreshWasCalled = true;
            }
        };
    }
}
