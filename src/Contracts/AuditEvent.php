<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

use DateTimeImmutable;
use InvalidArgumentException;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

final readonly class AuditEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public string $sourcePackage,
        public string $category,
        public string $type,
        public string $actor,
        public string $subject,
        public AuditSeverity $severity,
        public AuditRetentionClass $retentionClass,
        public string $correlationId,
        public DateTimeImmutable $occurredAt,
        public array $payload = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function create(
        string $sourcePackage,
        string $category,
        string $type,
        string $actor,
        string $subject,
        AuditSeverity $severity,
        AuditRetentionClass $retentionClass,
        string $correlationId,
        ?DateTimeImmutable $occurredAt = null,
        array $payload = [],
    ): self {
        foreach ([
            'sourcePackage' => $sourcePackage,
            'category' => $category,
            'type' => $type,
            'actor' => $actor,
            'subject' => $subject,
            'correlationId' => $correlationId,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("Audit event {$field} must not be empty.");
            }
        }

        return new self(
            sourcePackage: $sourcePackage,
            category: $category,
            type: $type,
            actor: $actor,
            subject: $subject,
            severity: $severity,
            retentionClass: $retentionClass,
            correlationId: $correlationId,
            occurredAt: $occurredAt ?? new DateTimeImmutable(),
            payload: $payload,
        );
    }
}
