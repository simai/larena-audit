<?php

declare(strict_types=1);

namespace Larena\Audit\Sinks;

use Illuminate\Database\ConnectionInterface;
use JsonException;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Contracts\AuditSink;

final readonly class DatabaseAuditSink implements AuditSink
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function accepts(AuditEventDescriptor $descriptor): bool
    {
        return true;
    }

    /** @throws JsonException */
    public function write(AuditEvent $event): void
    {
        $this->connection->table('larena_audit_events')->insert([
            'source_package' => $event->sourcePackage,
            'category' => $event->category,
            'event_type' => $event->type,
            'actor' => $event->actor,
            'subject' => $event->subject,
            'severity' => $event->severity->value,
            'retention_class' => $event->retentionClass->value,
            'correlation_id' => $event->correlationId,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            'payload' => json_encode($event->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'created_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);
    }
}
