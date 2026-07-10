<?php

declare(strict_types=1);

namespace Larena\Audit\Tests;

use Illuminate\Foundation\Application;
use Larena\Audit\Providers\AuditServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'larena-audit-');
        if ($path === false) {
            self::fail('Could not allocate a temporary SQLite database.');
        }

        $this->databasePath = $path;
        parent::setUp();
        $this->app['view']->addNamespace('larena-admin', __DIR__ . '/Fixtures/views');
        $this->artisan('migrate', ['--database' => 'audit_testing', '--force' => true])
            ->assertSuccessful();
    }

    protected function tearDown(): void
    {
        $databasePath = $this->databasePath;
        parent::tearDown();

        if (is_file($databasePath) && !unlink($databasePath)) {
            self::fail("Could not remove temporary SQLite database: {$databasePath}");
        }
    }

    /** @param Application $app */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'audit_testing');
        $app['config']->set('database.connections.audit_testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('larena-audit.admin.enabled', true);
        $app['config']->set('larena-audit.admin.middleware', []);
    }

    /** @param Application $app
     *  @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [AuditServiceProvider::class];
    }
}
