<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DatabaseAuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\Sinks\DatabaseAuditSink;

require __DIR__ . '/../../vendor/autoload.php';

function assert_database_audit_pipeline_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function database_audit_pipeline_descriptor(): AuditEventDescriptor
{
    return new class implements AuditEventDescriptor {
        public function sourcePackage(): string { return 'larena/auth'; }
        public function category(): string { return 'security'; }
        public function type(): string { return 'auth.login.denied'; }
        public function severity(): AuditSeverity { return AuditSeverity::Security; }
        public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Security; }
        public function redactedPayloadFields(): array { return ['token']; }
        public function forbiddenPayloadFields(): array { return ['password']; }
        public function isExperimental(): bool { return false; }
    };
}

/** @param array<array-key, mixed> $payload */
function database_audit_pipeline_event(
    string $correlationId,
    array $payload,
    string $type = 'auth.login.denied',
): AuditEvent {
    return AuditEvent::create(
        sourcePackage: 'larena/auth',
        category: 'security',
        type: $type,
        actor: 'user:42',
        subject: 'account:42',
        severity: AuditSeverity::Security,
        retentionClass: AuditRetentionClass::Security,
        correlationId: $correlationId,
        payload: $payload,
    );
}

function database_audit_pipeline_migrate(Connection $connection, bool $up): void
{
    Schema::swap($connection->getSchemaBuilder());
    $migration = require __DIR__ . '/../../database/migrations/2026_07_09_000001_create_larena_audit_events_table.php';
    $up ? $migration->up() : $migration->down();
}

$primaryPath = tempnam(sys_get_temp_dir(), 'larena-audit-pipeline-primary-');
$secondaryPath = tempnam(sys_get_temp_dir(), 'larena-audit-pipeline-secondary-');
if ($primaryPath === false || $secondaryPath === false) {
    throw new RuntimeException('Could not allocate Audit pipeline test databases.');
}

$container = new Application();
$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $primaryPath,
    'prefix' => '',
    'foreign_key_constraints' => true,
], 'primary');
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $secondaryPath,
    'prefix' => '',
    'foreign_key_constraints' => true,
], 'secondary');
$capsule->getDatabaseManager()->setDefaultConnection('primary');
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container->instance('db', $capsule->getDatabaseManager());
Facade::setFacadeApplication($container);

$primary = $capsule->getConnection('primary');
$secondary = $capsule->getConnection('secondary');

try {
    database_audit_pipeline_migrate($primary, true);
    database_audit_pipeline_migrate($secondary, true);
    Schema::swap($primary->getSchemaBuilder());

    $pipeline = new DatabaseAuditEventPipeline($primary);
    assert_database_audit_pipeline_true(
        $pipeline->connection() === $primary,
        'Connection-bound pipeline must return the exact constructor connection object.',
    );
    assert_database_audit_pipeline_true(
        $pipeline->connection() !== $secondary,
        'Different connection objects must remain distinguishable before mutation.',
    );

    $pipelineProperty = new ReflectionProperty(DatabaseAuditEventPipeline::class, 'pipeline');
    $genericPipeline = $pipelineProperty->getValue($pipeline);
    assert_database_audit_pipeline_true(
        $genericPipeline instanceof AuditEventPipeline,
        'Database pipeline must compose the existing generic AuditEventPipeline.',
    );
    $redactorProperty = new ReflectionProperty(AuditEventPipeline::class, 'redactor');
    assert_database_audit_pipeline_true(
        $redactorProperty->getValue($genericPipeline) instanceof DefaultAuditRedactor,
        'Database pipeline must use DefaultAuditRedactor.',
    );
    $sinksProperty = new ReflectionProperty(AuditEventPipeline::class, 'sinks');
    $sinks = $sinksProperty->getValue($genericPipeline);
    assert_database_audit_pipeline_true(
        is_array($sinks) && count($sinks) === 1 && $sinks[0] instanceof DatabaseAuditSink,
        'Connection-bound pipeline must contain exactly one DatabaseAuditSink.',
    );
    $sinkConnectionProperty = new ReflectionProperty(DatabaseAuditSink::class, 'connection');
    assert_database_audit_pipeline_true(
        $sinkConnectionProperty->getValue($sinks[0]) === $primary,
        'The only database sink must use the exact constructor connection object.',
    );

    assert_database_audit_pipeline_true($primary->transactionLevel() === 0, 'Test must start outside a transaction.');
    $primary->beginTransaction();
    assert_database_audit_pipeline_true($primary->transactionLevel() === 1, 'Caller transaction must be active.');
    $rolledBack = $pipeline->route(
        database_audit_pipeline_descriptor(),
        database_audit_pipeline_event('audit-pipeline-rollback', ['token' => 'must-not-persist']),
    );
    assert_database_audit_pipeline_true(
        $rolledBack->payload['token'] === DefaultAuditRedactor::REDACTED_VALUE,
        'Route must return the redacted event.',
    );
    assert_database_audit_pipeline_true(
        $primary->transactionLevel() === 1,
        'Audit route must not commit, roll back or nest the caller transaction.',
    );
    assert_database_audit_pipeline_true(
        $primary->table('larena_audit_events')->count() === 1,
        'Audit row must participate in the active caller transaction.',
    );
    $primary->rollBack();
    assert_database_audit_pipeline_true($primary->transactionLevel() === 0, 'Caller rollback must close its transaction.');
    assert_database_audit_pipeline_true(
        $primary->table('larena_audit_events')->count() === 0,
        'Caller rollback must remove the Audit row.',
    );

    $primary->beginTransaction();
    $committed = $pipeline->route(
        database_audit_pipeline_descriptor(),
        database_audit_pipeline_event(
            'audit-pipeline-commit',
            ['context' => ['attempts' => [['token' => 'must-be-redacted']]]],
        ),
    );
    assert_database_audit_pipeline_true($primary->transactionLevel() === 1, 'Audit route must leave commit ownership to caller.');
    $primary->commit();
    assert_database_audit_pipeline_true($primary->transactionLevel() === 0, 'Caller commit must close its transaction.');
    assert_database_audit_pipeline_true(
        $committed->payload['context']['attempts'][0]['token'] === DefaultAuditRedactor::REDACTED_VALUE,
        'Nested sensitive payload must be redacted in the returned event.',
    );
    $storedPayload = json_decode(
        (string) $primary->table('larena_audit_events')->value('payload'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    assert_database_audit_pipeline_true(
        $storedPayload['context']['attempts'][0]['token'] === DefaultAuditRedactor::REDACTED_VALUE,
        'Nested sensitive payload must be redacted before durable storage.',
    );
    assert_database_audit_pipeline_true(
        $secondary->table('larena_audit_events')->count() === 0,
        'Connection-bound pipeline must not write to another connection.',
    );

    $baselineCount = $primary->table('larena_audit_events')->count();
    $descriptorMismatchFailed = false;
    try {
        $pipeline->route(
            database_audit_pipeline_descriptor(),
            database_audit_pipeline_event('audit-pipeline-mismatch', ['safe' => true], 'auth.login.allowed'),
        );
    } catch (InvalidArgumentException) {
        $descriptorMismatchFailed = true;
    }
    assert_database_audit_pipeline_true($descriptorMismatchFailed, 'Descriptor mismatch must propagate.');
    assert_database_audit_pipeline_true(
        $primary->table('larena_audit_events')->count() === $baselineCount,
        'Descriptor mismatch must not persist an Audit row.',
    );

    $forbiddenFailed = false;
    try {
        $pipeline->route(
            database_audit_pipeline_descriptor(),
            database_audit_pipeline_event(
                'audit-pipeline-forbidden',
                ['nested' => ['attempts' => [['password' => 'must-never-persist']]]],
            ),
        );
    } catch (InvalidArgumentException) {
        $forbiddenFailed = true;
    }
    assert_database_audit_pipeline_true($forbiddenFailed, 'Nested forbidden payload must propagate.');
    assert_database_audit_pipeline_true(
        $primary->table('larena_audit_events')->count() === $baselineCount,
        'Forbidden payload must not persist an Audit row.',
    );

    $resource = fopen('php://memory', 'rb');
    if ($resource === false) {
        throw new RuntimeException('Could not allocate JSON failure fixture.');
    }
    $jsonFailed = false;
    try {
        $pipeline->route(
            database_audit_pipeline_descriptor(),
            database_audit_pipeline_event('audit-pipeline-json', ['unsupported' => $resource]),
        );
    } catch (JsonException) {
        $jsonFailed = true;
    } finally {
        fclose($resource);
    }
    assert_database_audit_pipeline_true($jsonFailed, 'JSON encoding failure must propagate.');
    assert_database_audit_pipeline_true(
        $primary->table('larena_audit_events')->count() === $baselineCount,
        'JSON encoding failure must not persist an Audit row.',
    );

    $capsule->getDatabaseManager()->purge('primary');
    $reconnected = $capsule->getDatabaseManager()->connection('primary');
    assert_database_audit_pipeline_true(
        $reconnected !== $primary,
        'Purge and reconnect must produce a distinguishable connection object.',
    );
    assert_database_audit_pipeline_true(
        $reconnected->table('larena_audit_events')->where('correlation_id', 'audit-pipeline-commit')->count() === 1,
        'Committed Audit row must persist across a fresh connection.',
    );

    database_audit_pipeline_migrate($reconnected, false);
    $databaseFailed = false;
    try {
        (new DatabaseAuditEventPipeline($reconnected))->route(
            database_audit_pipeline_descriptor(),
            database_audit_pipeline_event('audit-pipeline-db-failure', ['safe' => true]),
        );
    } catch (QueryException) {
        $databaseFailed = true;
    }
    assert_database_audit_pipeline_true($databaseFailed, 'Database failure must propagate without synthetic success.');
    assert_database_audit_pipeline_true(
        $reconnected->transactionLevel() === 0,
        'Database failure must not leave a transaction owned by Audit.',
    );
    database_audit_pipeline_migrate($reconnected, true);

    database_audit_pipeline_migrate($secondary, false);
    database_audit_pipeline_migrate($reconnected, false);
} finally {
    $capsule->getDatabaseManager()->disconnect('primary');
    $capsule->getDatabaseManager()->disconnect('secondary');
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    foreach ([$primaryPath, $secondaryPath] as $path) {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException("Could not remove Audit pipeline test database: {$path}");
        }
    }
}

echo "DatabaseAuditEventPipelineTest passed.\n";
