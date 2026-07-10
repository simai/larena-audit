<?php

declare(strict_types=1);

namespace Larena\Audit\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Audit\Contracts\AuditSink;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\ReadModel\AuditHistoryReader;
use Larena\Audit\Sinks\DatabaseAuditSink;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/larena-audit.php', 'larena-audit');
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
        $this->app->bind(AuditHistoryReader::class, static function (Application $app): AuditHistoryReader {
            /** @var DatabaseManager $database */
            $database = $app->make(DatabaseManager::class);

            return new AuditHistoryReader(
                $database->connection(),
                (int) $app->make(ConfigRepository::class)->get('larena-audit.admin.limit', 100),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'larena-audit');

        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        if ($this->app->environment((array) $config->get('larena-audit.admin.allowed_environments', ['local', 'testing']))
            && (bool) $config->get('larena-audit.admin.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
        }
    }
}
