<?php

declare(strict_types=1);

namespace Larena\Audit\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Audit\Contracts\AuditSink;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\Sinks\DatabaseAuditSink;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DatabaseAuditSink::class, static function (Application $app): DatabaseAuditSink {
            /** @var DatabaseManager $database */
            $database = $app->make(DatabaseManager::class);

            return new DatabaseAuditSink($database->connection());
        });
        $this->app->bind(AuditSink::class, DatabaseAuditSink::class);
        $this->app->bind(AuditEventPipeline::class, static function (Application $app): AuditEventPipeline {
            return new AuditEventPipeline(
                new DefaultAuditRedactor(),
                [$app->make(DatabaseAuditSink::class)],
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
