<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

interface AuditSink
{
    public function accepts(AuditEventDescriptor $descriptor): bool;

    public function write(AuditEvent $event): void;
}
