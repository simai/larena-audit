<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Enums/AuditSeverity.php';
require_once __DIR__ . '/../../src/Enums/AuditRetentionClass.php';
require_once __DIR__ . '/../../src/Contracts/AuditEvent.php';
require_once __DIR__ . '/../../src/Contracts/AuditRedactor.php';

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditRedactor;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

function requireTrue(bool $actual, string $message): void
{
    if ($actual !== true) {
        throw new RuntimeException($message);
    }
}

function requireSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

$severityFailedClosed = false;

try {
    AuditSeverity::from('unsafe');
} catch (ValueError) {
    $severityFailedClosed = true;
}

requireTrue($severityFailedClosed, 'unknown audit severity must fail closed');

$retentionFailedClosed = false;

try {
    AuditRetentionClass::from('forever');
} catch (ValueError) {
    $retentionFailedClosed = true;
}

requireTrue($retentionFailedClosed, 'unknown audit retention class must fail closed');

$redactor = new class implements AuditRedactor {
    public function redact(AuditEvent $event): AuditEvent
    {
        return $event;
    }

    public function redactPayload(array $payload, array $redactedKeys): array
    {
        foreach ($redactedKeys as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
};

$payload = $redactor->redactPayload([
    'token' => 'secret-token',
    'status' => 'denied',
], ['token']);

requireSame('[redacted]', $payload['token'], 'configured sensitive key must be redacted');
requireSame('denied', $payload['status'], 'non-sensitive payload value must be preserved');

echo "Audit fail-closed test passed.\n";
