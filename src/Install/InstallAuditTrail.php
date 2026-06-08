<?php

declare(strict_types=1);

namespace Larena\Audit\Install;

use InvalidArgumentException;

final class InstallAuditTrail
{
    /**
     * @return list<array<string, string>>
     */
    public static function plannedTables(): array
    {
        return [
            [
                'name' => 'larena_install_events',
                'owner' => 'larena/audit',
                'purpose' => 'Guarded installer action audit trail for launch-record-controlled operations.',
            ],
        ];
    }

    public static function migrationPath(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }

    /**
     * @param array<string, mixed> $launchRecord
     * @param array<string, mixed> $details
     *
     * @return array<string, mixed>
     */
    public static function eventPayload(array $launchRecord, string $operation, string $resultStatus, array $details = []): array
    {
        if (trim($operation) === '') {
            throw new InvalidArgumentException('Install audit operation must not be empty.');
        }

        if (trim($resultStatus) === '') {
            throw new InvalidArgumentException('Install audit result status must not be empty.');
        }

        $launchRecordId = self::stringValue($launchRecord['id'] ?? null, 'unknown-launch-record');
        $targetStep = self::stringValue($launchRecord['target_step'] ?? null, 'unknown-target-step');
        $correlationId = 'install:' . $launchRecordId . ':' . $operation;

        return [
            'schema' => 'larena.install_audit_event.v1',
            'event_key' => hash('sha256', $correlationId),
            'source_package' => 'larena/core',
            'category' => 'installer',
            'event_type' => 'installer.' . $operation,
            'actor' => self::actor($launchRecord),
            'subject' => $targetStep,
            'severity' => $resultStatus === 'passed' ? 'notice' : 'warning',
            'retention_class' => 'operational',
            'correlation_id' => $correlationId,
            'launch_record_id' => $launchRecordId,
            'target_step' => $targetStep,
            'result_status' => $resultStatus,
            'evidence_path' => self::stringValue($launchRecord['evidence_path'] ?? null, null),
            'payload' => [
                'operation' => $operation,
                'result_status' => $resultStatus,
                'launch_record_path' => self::stringValue($launchRecord['_relative_path'] ?? null, null),
                'limits' => is_array($launchRecord['limits'] ?? null) ? $launchRecord['limits'] : [],
                'details' => $details,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $launchRecord
     */
    private static function actor(array $launchRecord): string
    {
        $approval = $launchRecord['operator_approval'] ?? [];
        if (is_array($approval)) {
            $operator = self::stringValue($approval['operator'] ?? $approval['approved_by'] ?? null, null);
            if ($operator !== null) {
                return $operator;
            }
        }

        return 'operator:unknown';
    }

    private static function stringValue(mixed $value, ?string $fallback): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : $fallback;
    }
}
