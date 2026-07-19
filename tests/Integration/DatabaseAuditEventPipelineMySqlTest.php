<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;
use Larena\Audit\Runtime\DatabaseAuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/DatabaseAuditEventPipelineMySqlTestSupport.php';

$optIn = getenv('LARENA_AUDIT_CONNECTION_BOUND_MYSQL_TEST');
if (!is_string($optIn) || !filter_var($optIn, FILTER_VALIDATE_BOOL)) {
    echo "DatabaseAuditEventPipelineMySqlTest skipped (explicit opt-in required).\n";
    exit(0);
}

auditPipelineMySqlExpect(extension_loaded('pdo_mysql'), 'audit_pipeline_mysql_pdo_extension_missing');

$credentials = auditPipelineMySqlCredentials();
$database = 'larena_audit_pipeline_test_' . strtolower(bin2hex(random_bytes(6)));
$databaseAllowlist = '/\Alarena_audit_pipeline_test_[a-f0-9]{12}\z/D';
auditPipelineMySqlExpect(
    preg_match($databaseAllowlist, $database) === 1,
    'audit_pipeline_mysql_database_allowlist_failed',
);

$config = [
    'driver' => 'mysql',
    'host' => $credentials['host'],
    'port' => $credentials['port'],
    'database' => $database,
    'username' => $credentials['username'],
    'password' => $credentials['password'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
];

$descriptor = new class implements AuditEventDescriptor {
    public function sourcePackage(): string { return 'larena/auth'; }
    public function category(): string { return 'security'; }
    public function type(): string { return 'auth.login.denied'; }
    public function severity(): AuditSeverity { return AuditSeverity::Security; }
    public function retentionClass(): AuditRetentionClass { return AuditRetentionClass::Security; }
    public function redactedPayloadFields(): array { return ['token']; }
    public function forbiddenPayloadFields(): array { return ['password']; }
    public function isExperimental(): bool { return false; }
};

$event = static function (string $correlationId, array $payload, string $type = 'auth.login.denied'): AuditEvent {
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
};

$server = auditPipelineMySqlServer($credentials);
$existing = $server->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
$existing->execute([$database]);
auditPipelineMySqlExpect((int) $existing->fetchColumn() === 0, 'audit_pipeline_mysql_refusing_existing_database');

$cleanupPending = true;
$created = false;
$connection = null;
auditPipelineMySqlRegisterCleanup($cleanupPending, $database, $databaseAllowlist, $credentials);

try {
    $server->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = auditPipelineMySqlConnection($config);
    auditPipelineMySqlMigration($connection, true);
    auditPipelineMySqlExpect(Schema::hasTable('larena_audit_events'), 'audit_pipeline_mysql_table_missing');

    $pipeline = new DatabaseAuditEventPipeline($connection);
    auditPipelineMySqlExpect(
        $pipeline->connection() === $connection,
        'audit_pipeline_mysql_exact_connection_identity_failed',
    );
    auditPipelineMySqlExpect($connection->transactionLevel() === 0, 'audit_pipeline_mysql_initial_transaction_level_invalid');

    $connection->beginTransaction();
    $pipeline->route(
        $descriptor,
        $event('audit-pipeline-mysql-rollback', ['token' => 'must-not-persist']),
    );
    auditPipelineMySqlExpect(
        $connection->transactionLevel() === 1,
        'audit_pipeline_mysql_route_changed_caller_transaction_level',
    );
    auditPipelineMySqlExpect(
        $connection->table('larena_audit_events')->count() === 1,
        'audit_pipeline_mysql_transactional_row_missing',
    );
    $connection->rollBack();
    auditPipelineMySqlExpect($connection->transactionLevel() === 0, 'audit_pipeline_mysql_rollback_level_invalid');
    auditPipelineMySqlExpect(
        $connection->table('larena_audit_events')->count() === 0,
        'audit_pipeline_mysql_caller_rollback_kept_row',
    );

    $connection->beginTransaction();
    $returned = $pipeline->route(
        $descriptor,
        $event(
            'audit-pipeline-mysql-commit',
            ['context' => ['attempts' => [['token' => 'must-be-redacted']]]],
        ),
    );
    auditPipelineMySqlExpect(
        $returned->payload['context']['attempts'][0]['token'] === DefaultAuditRedactor::REDACTED_VALUE,
        'audit_pipeline_mysql_returned_event_not_redacted',
    );
    auditPipelineMySqlExpect(
        $connection->transactionLevel() === 1,
        'audit_pipeline_mysql_route_owned_commit',
    );
    $connection->commit();
    auditPipelineMySqlExpect($connection->transactionLevel() === 0, 'audit_pipeline_mysql_commit_level_invalid');
    $storedPayload = json_decode(
        (string) $connection->table('larena_audit_events')->value('payload'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    auditPipelineMySqlExpect(
        $storedPayload['context']['attempts'][0]['token'] === DefaultAuditRedactor::REDACTED_VALUE,
        'audit_pipeline_mysql_persisted_payload_not_redacted',
    );

    $baselineCount = $connection->table('larena_audit_events')->count();
    $descriptorFailed = false;
    try {
        $pipeline->route(
            $descriptor,
            $event('audit-pipeline-mysql-mismatch', ['safe' => true], 'auth.login.allowed'),
        );
    } catch (InvalidArgumentException) {
        $descriptorFailed = true;
    }
    auditPipelineMySqlExpect($descriptorFailed, 'audit_pipeline_mysql_descriptor_mismatch_not_propagated');
    auditPipelineMySqlExpect(
        $connection->table('larena_audit_events')->count() === $baselineCount,
        'audit_pipeline_mysql_descriptor_mismatch_persisted_row',
    );

    $forbiddenFailed = false;
    try {
        $pipeline->route(
            $descriptor,
            $event(
                'audit-pipeline-mysql-forbidden',
                ['nested' => ['attempts' => [['password' => 'must-never-persist']]]],
            ),
        );
    } catch (InvalidArgumentException) {
        $forbiddenFailed = true;
    }
    auditPipelineMySqlExpect($forbiddenFailed, 'audit_pipeline_mysql_forbidden_field_not_propagated');
    auditPipelineMySqlExpect(
        $connection->table('larena_audit_events')->count() === $baselineCount,
        'audit_pipeline_mysql_forbidden_field_persisted_row',
    );

    $originalConnection = $connection;
    $connection->disconnect();
    $connection = auditPipelineMySqlConnection($config);
    auditPipelineMySqlExpect(
        $connection !== $originalConnection,
        'audit_pipeline_mysql_reconnect_identity_not_distinguishable',
    );
    auditPipelineMySqlExpect(
        $connection->table('larena_audit_events')
            ->where('correlation_id', 'audit-pipeline-mysql-commit')
            ->count() === 1,
        'audit_pipeline_mysql_committed_row_missing_after_reconnect',
    );

    auditPipelineMySqlMigration($connection, false);
    auditPipelineMySqlExpect(!Schema::hasTable('larena_audit_events'), 'audit_pipeline_mysql_migration_down_failed');
    $databaseFailed = false;
    try {
        (new DatabaseAuditEventPipeline($connection))->route(
            $descriptor,
            $event('audit-pipeline-mysql-db-failure', ['safe' => true]),
        );
    } catch (QueryException) {
        $databaseFailed = true;
    }
    auditPipelineMySqlExpect($databaseFailed, 'audit_pipeline_mysql_database_error_not_propagated');
    auditPipelineMySqlExpect(
        $connection->transactionLevel() === 0,
        'audit_pipeline_mysql_database_error_left_audit_transaction',
    );

    auditPipelineMySqlMigration($connection, true);
    auditPipelineMySqlExpect(Schema::hasTable('larena_audit_events'), 'audit_pipeline_mysql_migration_reapply_failed');
    auditPipelineMySqlMigration($connection, false);
    auditPipelineMySqlExpect(!Schema::hasTable('larena_audit_events'), 'audit_pipeline_mysql_final_rollback_failed');
} finally {
    if ($connection instanceof Connection) {
        $connection->disconnect();
    }
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);

    if ($created) {
        auditPipelineMySqlExpect(
            preg_match($databaseAllowlist, $database) === 1,
            'audit_pipeline_mysql_cleanup_allowlist_failed',
        );
        $server = auditPipelineMySqlServer($credentials);
        $server->exec('DROP DATABASE `' . $database . '`');
        $remaining = $server->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $remaining->execute([$database]);
        auditPipelineMySqlExpect(
            (int) $remaining->fetchColumn() === 0,
            'audit_pipeline_mysql_cleanup_schema_remaining',
        );
    }
    $cleanupPending = false;
}

echo "DatabaseAuditEventPipelineMySqlTest passed; isolated schema cleanup verified.\n";
