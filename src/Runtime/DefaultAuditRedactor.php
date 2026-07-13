<?php

declare(strict_types=1);

namespace Larena\Audit\Runtime;

use InvalidArgumentException;
use Larena\Audit\Contracts\AuditRedactor;

final readonly class DefaultAuditRedactor implements AuditRedactor
{
    public const REDACTED_VALUE = '[redacted]';

    private const MAX_PAYLOAD_DEPTH = 64;

    public function redact(array $payload, array $redactedFields, array $forbiddenFields): array
    {
        $redactedFields = $this->validatedFields($redactedFields, 'redacted');
        $forbiddenFields = $this->validatedFields($forbiddenFields, 'forbidden');

        $this->assertNoForbiddenFields($payload, $forbiddenFields, 0);

        return $this->redactArray($payload, $redactedFields, 0);
    }

    /**
     * @param list<string> $redactedFields
     * @return array<array-key, mixed>
     */
    private function redactArray(
        array $payload,
        array $redactedFields,
        int $depth,
    ): array {
        if ($depth > self::MAX_PAYLOAD_DEPTH) {
            throw new InvalidArgumentException('Audit payload nesting exceeds the safe redaction depth.');
        }

        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array($key, $redactedFields, true)) {
                $payload[$key] = self::REDACTED_VALUE;

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redactArray(
                    $value,
                    $redactedFields,
                    $depth + 1,
                );
            }
        }

        return $payload;
    }

    /**
     * @param list<string> $forbiddenFields
     */
    private function assertNoForbiddenFields(array $payload, array $forbiddenFields, int $depth): void
    {
        if ($depth > self::MAX_PAYLOAD_DEPTH) {
            throw new InvalidArgumentException('Audit payload nesting exceeds the safe redaction depth.');
        }

        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array($key, $forbiddenFields, true)) {
                throw new InvalidArgumentException("Audit payload contains forbidden field: {$key}");
            }

            if (is_array($value)) {
                $this->assertNoForbiddenFields($value, $forbiddenFields, $depth + 1);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return list<string>
     */
    private function validatedFields(array $fields, string $kind): array
    {
        $validated = [];
        foreach ($fields as $field) {
            if (!is_string($field) || trim($field) === '') {
                throw new InvalidArgumentException("Audit {$kind} payload field names must be non-empty strings.");
            }

            $validated[] = $field;
        }

        return array_values(array_unique($validated));
    }
}
