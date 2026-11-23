<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Guard the effective configuration before Laravel sets up database traits.
     * RefreshDatabase's own hook would shadow an inherited hook in this class.
     */
    final protected function setUpTraits(): array
    {
        $this->ensureTestDatabaseIsIsolated();

        return parent::setUpTraits();
    }

    final protected function ensureTestDatabaseIsIsolated(): void
    {
        $config = $this->app->make('config');
        $connection = $config->get('database.connections.sqlite', []);
        $connectionsToTransact = method_exists($this, 'connectionsToTransact')
            ? $this->connectionsToTransact()
            : [null];

        $checks = [
            'application environment' => $this->app->environment('testing'),
            'app.env' => $config->get('app.env') === 'testing',
            'database.default' => $config->get('database.default') === 'sqlite',
            'sqlite driver' => ($connection['driver'] ?? null) === 'sqlite',
            'in-memory database' => ($connection['database'] ?? null) === ':memory:',
            'empty DB_URL' => in_array($connection['url'] ?? null, [null, ''], true),
            'foreign keys enabled' => ($connection['foreign_key_constraints'] ?? null) === true,
            'no read/write overrides' => ! isset($connection['read']) && ! isset($connection['write']),
            'transaction connections' => in_array($connectionsToTransact, [[null], ['sqlite']], true),
        ];
        $failed = array_keys(array_filter($checks, fn (bool $safe): bool => ! $safe));

        if ($failed !== []) {
            throw new RuntimeException('Unsafe testing database configuration: '.implode(', ', $failed).'. Only testing / sqlite / :memory: without DB_URL is allowed. No migrations were started.');
        }

        // Explicit named connections must not be able to reach development either.
        $config->set('database.connections', ['sqlite' => $connection]);
    }
}
