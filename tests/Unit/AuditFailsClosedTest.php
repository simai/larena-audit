<?php

declare(strict_types=1);

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

require_once __DIR__ . '/../../vendor/autoload.php';

$failed = false;

try {
    AuditEvent::create(
        sourcePackage: '',
        category: 'security',
        type: 'auth.login.denied',
        actor: 'user:42',
        subject: 'account:42',
        severity: AuditSeverity::Security,
        retentionClass: AuditRetentionClass::Security,
        correlationId: 'corr-001',
    );
} catch (InvalidArgumentException) {
    $failed = true;
}

if (!$failed) {
    fwrite(STDERR, "AuditEvent should fail closed for empty source package.\n");
    exit(1);
}

echo "AuditFailsClosedTest passed.\n";
