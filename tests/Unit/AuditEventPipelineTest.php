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

function assert_pipeline_true(bool $condition, string $message): void
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

$sink = new InMemoryAuditSink();
$pipeline = new AuditEventPipeline(new DefaultAuditRedactor(), [$sink]);
$event = AuditEvent::create(
    sourcePackage: 'larena/auth',
    category: 'security',
    type: 'auth.login.denied',
    actor: 'user:42',
    subject: 'account:42',
    severity: AuditSeverity::Security,
    retentionClass: AuditRetentionClass::Security,
    correlationId: 'corr-002',
    payload: ['token' => 'raw-token', 'reason' => 'mfa_required'],
);

$redacted = $pipeline->route($descriptor, $event);

assert_pipeline_true($redacted->payload['token'] === DefaultAuditRedactor::REDACTED_VALUE, 'Pipeline must redact sensitive payload before routing.');
assert_pipeline_true($redacted->payload['reason'] === 'mfa_required', 'Pipeline must keep non-sensitive payload metadata.');
assert_pipeline_true(count($sink->events()) === 1, 'Accepted sink must receive one event.');
assert_pipeline_true($sink->events()[0]->payload['token'] === DefaultAuditRedactor::REDACTED_VALUE, 'Sink must receive redacted event only.');
assert_pipeline_true($sink->events()[0]->correlationId === 'corr-002', 'Correlation id must remain available after redaction.');

echo "AuditEventPipelineTest passed.\n";
