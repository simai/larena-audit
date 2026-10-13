<?php

declare(strict_types=1);

namespace Larena\Audit\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Access\Runtime\AccessOperationRegistry;
use Larena\Access\ValueObjects\AccessOperationDescriptor;
use Larena\Audit\Contracts\AuditSink;
use Larena\Audit\Contracts\ConnectionBoundAuditEventPipeline;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DatabaseAuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\ReadModel\AuditHistoryReader;
use Larena\Audit\Sinks\DatabaseAuditSink;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/larena-audit.php', 'larena-audit');
        // Core installs record their install audit trail here; without Core nothing is registered.
        if (interface_exists(\Larena\Core\Contracts\InstallAuditTrailAdapter::class)) {
            $this->app->singletonIf(\Larena\Core\Contracts\InstallAuditTrailAdapter::class, \Larena\Audit\Install\CoreInstallAuditTrailAdapter::class);
        }
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
        $this->app->bind(DatabaseAuditEventPipeline::class, static function (Application $app): DatabaseAuditEventPipeline {
            /** @var DatabaseManager $database */
            $database = $app->make(DatabaseManager::class);

            return new DatabaseAuditEventPipeline($database->connection());
        });
        $this->app->alias(DatabaseAuditEventPipeline::class, ConnectionBoundAuditEventPipeline::class);
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
        // The access bridge: with larena/access installed (a suggested package), the history read is an
        // access operation; the classes are named, not spelled as strings, so a rename breaks loudly.
        if (class_exists(AccessOperationRegistry::class) && class_exists(AccessOperationDescriptor::class)) {
            $this->app->make(AccessOperationRegistry::class)->register(new AccessOperationDescriptor(
                'audit.history.read', 'larena/audit', 'larena-audit::operations.history_read', 'audit.history:all', 'read', 'high',
            ));
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'larena-audit');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'larena-audit');

        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        // The history screen is the only thing in audit that renders anything: a page of the admin shell
        // (its layout larena-admin::layouts.app) built with larena/ui. Without both — a headless composition —
        // audit still records, reads and pipelines events; it just has no screen. The shell's views are known
        // once every provider has booted, so the route is decided then.
        if ($this->app->environment((array) $config->get('larena-audit.admin.allowed_environments', ['local', 'testing']))
            && (bool) $config->get('larena-audit.admin.enabled', false)
            && class_exists(\Larena\Ui\Smart::class)) {
            $this->app->booted(function (): void {
                if ($this->app->make('view')->exists('larena-admin::layouts.app')) {
                    $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
                }
            });
        }
    }
}
