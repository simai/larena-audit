<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

interface AuditSink
{
    public function name(): string;

    public function accepts(AuditEvent $event): bool;

    public function write(AuditEvent $event): void;
}
