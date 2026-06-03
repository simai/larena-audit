<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Enums/AuditSeverity.php';
require_once __DIR__ . '/../../src/Enums/AuditRetentionClass.php';
require_once __DIR__ . '/../../src/Contracts/AuditEvent.php';
require_once __DIR__ . '/../../src/Contracts/AuditEventDescriptor.php';
require_once __DIR__ . '/../../src/Contracts/AuditRedactor.php';
require_once __DIR__ . '/../../src/Contracts/AuditSink.php';

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

function requireSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$event = new class implements AuditEvent {
    public function sourcePackage(): string
    {
        return 'larena/auth';
    }

    public function category(): string
    {
        return 'auth';
    }

    public function type(): string
    {
        return 'login_denied';
    }

    public function actorType(): string
    {
        return 'user';
    }

    public function actorId(): string
    {
        return 'user-1';
    }

    public function subjectType(): string
    {
        return 'session';
    }

    public function subjectId(): string
    {
        return 'session-1';
    }

    public function severity(): AuditSeverity
    {
        return AuditSeverity::Security;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-06-03T00:00:00+00:00');
    }

    public function correlationId(): string
    {
        return 'corr-1';
    }

    public function retentionClass(): AuditRetentionClass
    {
        return AuditRetentionClass::Security;
    }

    public function payload(): array
    {
        return ['reason' => 'access_denied'];
    }
};

requireSame('larena/auth', $event->sourcePackage(), 'source package is required');
requireSame('auth', $event->category(), 'category is required');
requireSame('login_denied', $event->type(), 'type is required');
requireSame('user', $event->actorType(), 'actor type is required');
requireSame('user-1', $event->actorId(), 'actor id is required');
requireSame('session', $event->subjectType(), 'subject type is required');
requireSame('session-1', $event->subjectId(), 'subject id is required');
requireSame(AuditSeverity::Security, $event->severity(), 'severity enum is required');
requireSame('corr-1', $event->correlationId(), 'correlation id is required');
requireSame(AuditRetentionClass::Security, $event->retentionClass(), 'retention class enum is required');
requireSame(['reason' => 'access_denied'], $event->payload(), 'payload boundary is required');

echo "Audit event contract test passed.\n";
