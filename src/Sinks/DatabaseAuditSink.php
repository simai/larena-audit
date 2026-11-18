<?php

declare(strict_types=1);

namespace Larena\Audit\Sinks;

use Illuminate\Database\ConnectionInterface;
use JsonException;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Contracts\AuditOnBehalf;
use Larena\Audit\Contracts\AuditSink;
use Illuminate\Container\Container;
use Throwable;

final readonly class DatabaseAuditSink implements AuditSink
{
    public function __construct(private ConnectionInterface $connection, private ?AuditOnBehalf $onBehalf = null)
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
            'payload' => json_encode($this->payload($event), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'created_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * The event's payload, and the real actor beside it while someone acts in another person's name: the event keeps
     * the person acted as for its actor, the payload says who really did it (owner decision 2026-11-18).
     *
     * @return array<string, mixed>
     */
    private function payload(AuditEvent $event): array
    {
        $onBehalf = $this->onBehalf;
        if ($onBehalf === null) {
            try {
                $container = Container::getInstance();
                $onBehalf = $container->bound(AuditOnBehalf::class) ? $container->make(AuditOnBehalf::class) : null;
            } catch (Throwable) {
                $onBehalf = null;
            }
        }
        $real = $onBehalf?->realActor($event->actor);

        return is_string($real) && $real !== '' && $real !== $event->actor
            ? $event->payload + [AuditOnBehalf::FIELD => $real]
            : $event->payload;
    }
}
