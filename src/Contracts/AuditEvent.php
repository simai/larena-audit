<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

use DateTimeImmutable;
use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

interface AuditEvent
{
    public function sourcePackage(): string;

    public function category(): string;

    public function type(): string;

    public function actorType(): string;

    public function actorId(): string;

    public function subjectType(): string;

    public function subjectId(): string;

    public function severity(): AuditSeverity;

    public function occurredAt(): DateTimeImmutable;

    public function correlationId(): string;

    public function retentionClass(): AuditRetentionClass;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
