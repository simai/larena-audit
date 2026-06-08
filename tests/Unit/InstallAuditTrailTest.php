<?php

declare(strict_types=1);

use Larena\Audit\Install\InstallAuditTrail;

require_once __DIR__ . '/../../vendor/autoload.php';

function assert_install_audit_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$tables = InstallAuditTrail::plannedTables();
assert_install_audit_true(count($tables) === 1, 'Install audit trail must expose one audit-owned table in this batch.');
assert_install_audit_true($tables[0]['name'] === 'larena_install_events', 'Install audit table name must be larena_install_events.');
assert_install_audit_true($tables[0]['owner'] === 'larena/audit', 'Install audit table must be owned by larena/audit.');
assert_install_audit_true(is_dir(InstallAuditTrail::migrationPath()), 'Install audit migration path must exist.');

$event = InstallAuditTrail::eventPayload([
    'id' => 'install-audit-trail-test',
    '_relative_path' => 'docs/project-management/launch-records/install-audit-trail-test.json',
    'target_step' => 'install_audit_trail_apply',
    'evidence_path' => 'docs/project-management/evidence/install-audit-trail-test',
    'operator_approval' => [
        'operator' => 'tester',
    ],
    'limits' => [
        'requires_command_confirmation' => 'install_audit_trail_apply',
    ],
], 'install_audit_trail_apply', 'passed', ['example' => true]);

assert_install_audit_true($event['schema'] === 'larena.install_audit_event.v1', 'Install audit event schema must be stable.');
assert_install_audit_true($event['source_package'] === 'larena/core', 'Core must be the installer event source package.');
assert_install_audit_true($event['category'] === 'installer', 'Install event category must be installer.');
assert_install_audit_true($event['event_type'] === 'installer.install_audit_trail_apply', 'Install event type must include operation.');
assert_install_audit_true($event['actor'] === 'tester', 'Install audit event actor must use operator approval when present.');
assert_install_audit_true($event['subject'] === 'install_audit_trail_apply', 'Install audit event subject must be target step.');
assert_install_audit_true($event['retention_class'] === 'operational', 'Install audit event retention class must be operational.');
assert_install_audit_true($event['payload']['details']['example'] === true, 'Install audit event details must be carried.');

echo 'InstallAuditTrailTest passed.' . PHP_EOL;
