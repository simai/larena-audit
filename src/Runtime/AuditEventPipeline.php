<?php

declare(strict_types=1);

namespace Larena\Audit\Runtime;

use InvalidArgumentException;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Contracts\AuditEventDescriptor;
use Larena\Audit\Contracts\AuditRedactor;
use Larena\Audit\Contracts\AuditSink;

final readonly class AuditEventPipeline
{
    /**
     * @param list<AuditSink> $sinks
     */
    public function __construct(
        private AuditRedactor $redactor,
        private array $sinks,
    ) {
    }

    public function route(AuditEventDescriptor $descriptor, AuditEvent $event): AuditEvent
    {
        $acceptedSinks = array_values(array_filter(
            $this->sinks,
            static fn (AuditSink $sink): bool => $sink->accepts($descriptor),
        ));

        if ($acceptedSinks === []) {
            throw new InvalidArgumentException('No audit sink accepts the event descriptor.');
        }

        $redactedEvent = AuditEvent::create(
            sourcePackage: $event->sourcePackage,
            category: $event->category,
            type: $event->type,
            actor: $event->actor,
            subject: $event->subject,
            severity: $event->severity,
            retentionClass: $event->retentionClass,
            correlationId: $event->correlationId,
            occurredAt: $event->occurredAt,
            payload: $this->redactor->redact(
                $event->payload,
                $descriptor->redactedPayloadFields(),
                $descriptor->forbiddenPayloadFields(),
            ),
        );

        foreach ($acceptedSinks as $sink) {
            $sink->write($redactedEvent);
        }

        return $redactedEvent;
    }
}
