<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

use Illuminate\Database\ConnectionInterface;

interface ConnectionBoundAuditEventPipeline
{
    public function connection(): ConnectionInterface;

    public function route(AuditEventDescriptor $descriptor, AuditEvent $event): AuditEvent;
}
