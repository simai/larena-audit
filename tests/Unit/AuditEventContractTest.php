<?php

declare(strict_types=1);

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

require_once __DIR__ . '/../../vendor/autoload.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$event = AuditEvent::create(
    sourcePackage: 'larena/auth',
    category: 'security',
    type: 'auth.login.denied',
    actor: 'user:42',
    subject: 'account:42',
    severity: AuditSeverity::Security,
    retentionClass: AuditRetentionClass::Security,
    correlationId: 'corr-001',
    payload: ['reason' => 'mfa_required'],
);

assert_true($event->sourcePackage === 'larena/auth', 'source package should be stored');
assert_true($event->severity->isSecurityRelevant(), 'security severity should be security relevant');
assert_true($event->retentionClass->requiresExplicitPolicy(), 'security retention should require explicit policy');
assert_true($event->payload['reason'] === 'mfa_required', 'payload should be carried without mutation by contract object');

echo "AuditEventContractTest passed.\n";
