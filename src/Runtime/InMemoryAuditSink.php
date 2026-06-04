<?php

declare(strict_types=1);

namespace Larena\Audit\Runtime;

use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Contracts\AuditSink;

final class InMemoryAuditSink implements AuditSink
{
    /**
     * @var list<AuditEvent>
     */
    private array $events = [];

    public function __construct(private readonly bool $acceptsEvents = true)
    {
    }

    public function accepts(AuditEventDescriptor $descriptor): bool
    {
        return $this->acceptsEvents;
    }

    public function write(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<AuditEvent>
     */
    public function events(): array
    {
        return $this->events;
    }
}
