<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\Sinks\DatabaseAuditSink;

require __DIR__ . '/../../vendor/autoload.php';

function assert_database_audit_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$databasePath = tempnam(sys_get_temp_dir(), 'larena-audit-');
if ($databasePath === false) {
    throw new RuntimeException('Could not allocate the audit test database.');
}

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $databasePath,
    'prefix' => '',
    'foreign_key_constraints' => true,
]);
$container = new Application();
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container->instance('db', $capsule->getDatabaseManager());
$container->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
Facade::setFacadeApplication($container);

$migration = require __DIR__ . '/../../database/migrations/2026_07_09_000001_create_larena_audit_events_table.php';

try {
    $migration->up();
    assert_database_audit_true(Schema::hasTable('larena_audit_events'), 'Audit migration must create its table.');

    $descriptor = new class implements AuditEventDescriptor {
        public function sourcePackage(): string { return 'larena/docara'; }
        public function category(): string { return 'content'; }
        public function type(): string { return 'docara.page.published'; }
        public function severity(): AuditSeverity { return AuditSeverity::Info; }
        public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Operational; }
        public function redactedPayloadFields(): array { return ['token']; }
        public function forbiddenPayloadFields(): array { return ['password']; }
        public function isExperimental(): bool { return false; }
    };

    $pipeline = new AuditEventPipeline(
        new DefaultAuditRedactor(),
        [new DatabaseAuditSink($capsule->getConnection())],
    );
    $pipeline->route($descriptor, AuditEvent::create(
        sourcePackage: 'larena/docara',
        category: 'content',
        type: 'docara.page.published',
        actor: 'user:admin_identity:1',
        subject: 'docara:page:welcome',
        severity: AuditSeverity::Info,
        retentionClass: AuditRetentionClass::Operational,
        correlationId: 'docara-publish-1',
        payload: ['slug' => 'welcome', 'token' => 'must-not-persist'],
    ));

    $capsule->getDatabaseManager()->purge();
    $reconnected = $capsule->getDatabaseManager()->connection();
    $container->instance('db.schema', $reconnected->getSchemaBuilder());
    Facade::clearResolvedInstance('db.schema');
    $row = $reconnected->table('larena_audit_events')->first();
    assert_database_audit_true(is_object($row), 'Audit event must persist across a new connection.');
    $payload = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);
    assert_database_audit_true($payload['token'] === DefaultAuditRedactor::REDACTED_VALUE, 'Sensitive payload must be redacted before persistence.');
    assert_database_audit_true($row->event_type === 'docara.page.published', 'Event type must persist.');

    $migration->down();
    assert_database_audit_true(!Schema::hasTable('larena_audit_events'), 'Audit rollback must remove its table.');
} finally {
    $capsule->getDatabaseManager()->disconnect();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    if (is_file($databasePath) && !unlink($databasePath)) {
        throw new RuntimeException('Could not remove the audit test database.');
    }
}

echo "DatabaseAuditSinkTest passed.\n";
