<?php

declare(strict_types=1);

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\Runtime\InMemoryAuditSink;

require_once __DIR__ . '/../../vendor/autoload.php';

function assert_pipeline_fail_closed_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$descriptor = new class implements AuditEventDescriptor {
    public function sourcePackage(): string
    {
        return 'larena/auth';
    }

    public function category(): string
    {
        return 'security';
    }

    public function type(): string
    {
        return 'auth.login.denied';
    }

    public function severity(): AuditSeverity
    {
        return AuditSeverity::Security;
    }

    public function retentionClass(): AuditRetentionClass
    {
        return AuditRetentionClass::Security;
    }

    public function redactedPayloadFields(): array
    {
        return ['token'];
    }

    public function forbiddenPayloadFields(): array
    {
        return ['password'];
    }

    public function isExperimental(): bool
    {
        return false;
    }
};

$event = AuditEvent::create(
    sourcePackage: 'larena/auth',
    category: 'security',
    type: 'auth.login.denied',
    actor: 'user:42',
    subject: 'account:42',
    severity: AuditSeverity::Security,
    retentionClass: AuditRetentionClass::Security,
    correlationId: 'corr-003',
    payload: ['password' => 'raw-password'],
);

$sink = new InMemoryAuditSink();
$pipeline = new AuditEventPipeline(new DefaultAuditRedactor(), [$sink]);
$forbiddenFailed = false;

try {
    $pipeline->route($descriptor, $event);
} catch (InvalidArgumentException) {
    $forbiddenFailed = true;
}

assert_pipeline_fail_closed_true($forbiddenFailed, 'Forbidden payload field must fail closed.');
assert_pipeline_fail_closed_true(count($sink->events()) === 0, 'Forbidden payload field must not be routed.');

$rejectingSink = new InMemoryAuditSink(false);
$noSinkPipeline = new AuditEventPipeline(new DefaultAuditRedactor(), [$rejectingSink]);
$noSinkFailed = false;

try {
    $noSinkPipeline->route(
        $descriptor,
        AuditEvent::create(
            sourcePackage: 'larena/auth',
            category: 'security',
            type: 'auth.login.denied',
            actor: 'user:42',
            subject: 'account:42',
            severity: AuditSeverity::Security,
            retentionClass: AuditRetentionClass::Security,
            correlationId: 'corr-004',
            payload: ['token' => 'raw-token'],
        ),
    );
} catch (InvalidArgumentException) {
    $noSinkFailed = true;
}

assert_pipeline_fail_closed_true($noSinkFailed, 'Missing accepting sink must fail closed.');
assert_pipeline_fail_closed_true(count($rejectingSink->events()) === 0, 'Rejecting sink must not receive event.');

echo "AuditEventPipelineFailsClosedTest passed.\n";
