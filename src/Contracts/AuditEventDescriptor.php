<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

interface AuditEventDescriptor
{
    public function sourcePackage(): string;

    /**
     * @return list<string>
     */
    public function categories(): array;

    /**
     * @return list<string>
     */
    public function eventTypesFor(string $category): array;

    public function severityFor(string $category, string $eventType): AuditSeverity;

    public function retentionClassFor(string $category, string $eventType): AuditRetentionClass;

    /**
     * @return list<string>
     */
    public function redactedPayloadKeysFor(string $category, string $eventType): array;
}
