<?php

declare(strict_types=1);

namespace Larena\Audit\Runtime;

use InvalidArgumentException;
use Larena\Audit\Contracts\AuditRedactor;

final readonly class DefaultAuditRedactor implements AuditRedactor
{
    public const REDACTED_VALUE = '[redacted]';

    public function redact(array $payload, array $redactedFields, array $forbiddenFields): array
    {
        foreach ($forbiddenFields as $field) {
            if (array_key_exists($field, $payload)) {
                throw new InvalidArgumentException("Audit payload contains forbidden field: {$field}");
            }
        }

        foreach ($redactedFields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = self::REDACTED_VALUE;
            }
        }

        return $payload;
    }
}
