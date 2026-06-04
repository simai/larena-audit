<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

interface AuditRedactor
{
    /**
     * @param array<string, mixed> $payload
     * @param list<string> $redactedFields
     * @param list<string> $forbiddenFields
     *
     * @return array<string, mixed>
     */
    public function redact(array $payload, array $redactedFields, array $forbiddenFields): array;
}
