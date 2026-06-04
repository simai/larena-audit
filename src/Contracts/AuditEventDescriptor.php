<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

use Larena\Audit\Enums\AuditRetentionClass;
use Larena\Audit\Enums\AuditSeverity;

interface AuditEventDescriptor
{
    public function sourcePackage(): string;

    public function category(): string;

    public function type(): string;

    public function severity(): AuditSeverity;

    public function retentionClass(): AuditRetentionClass;

    /**
     * @return list<string>
     */
    public function redactedPayloadFields(): array;

    /**
     * @return list<string>
     */
    public function forbiddenPayloadFields(): array;

    public function isExperimental(): bool;
}
