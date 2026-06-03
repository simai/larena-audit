<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

interface AuditRedactor
{
    public function redact(AuditEvent $event): AuditEvent;

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $redactedKeys
     *
     * @return array<string, mixed>
     */
    public function redactPayload(array $payload, array $redactedKeys): array;
}
