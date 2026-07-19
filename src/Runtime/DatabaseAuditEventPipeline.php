<?php

declare(strict_types=1);

namespace Larena\Audit\Runtime;

use Illuminate\Database\ConnectionInterface;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Contracts\ConnectionBoundAuditEventPipeline;
use Larena\Audit\Sinks\DatabaseAuditSink;

final readonly class DatabaseAuditEventPipeline implements ConnectionBoundAuditEventPipeline
{
    private AuditEventPipeline $pipeline;

    public function __construct(private ConnectionInterface $connection)
    {
        $this->pipeline = new AuditEventPipeline(
            new DefaultAuditRedactor(),
            [new DatabaseAuditSink($connection)],
        );
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function route(AuditEventDescriptor $descriptor, AuditEvent $event): AuditEvent
    {
        return $this->pipeline->route($descriptor, $event);
    }
}
