<?php

declare(strict_types=1);

namespace Larena\Audit\ReadModel;

use Illuminate\Database\ConnectionInterface;
use JsonException;
use stdClass;

final readonly class AuditHistoryReader
{
    /**
     * @param list<array{prefix: string, label: string, words?: string}> $sources the event families other packages declare
     *        for the history (config `larena-audit.history_sources`): every event whose type starts with `prefix`, named by
     *        `label`, a translation key of the declaring package; `words`, a translation group of it, says each event in
     *        a sentence (its key: the type with dots as underscores)
     */
    public function __construct(
        private ConnectionInterface $connection,
        private int $limit = 100,
        private array $sources = [],
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        return $this->connection->table('larena_audit_events')
            ->where(function ($query): void {
                $query->whereIn('event_type', self::FILE_EVENTS)->orWhere('event_type', 'like', 'auth.%')
                    ->orWhere('event_type', 'like', 'access.%')
                    ->orWhere('event_type', 'like', 'storage.%');
                foreach ($this->sources as $source) {
                    // An exact prefix: a LIKE pattern would read `_` as any character.
                    $query->orWhereRaw('substr(event_type, 1, ?) = ?', [strlen($source['prefix']), $source['prefix']]);
                }
            })
            ->orderByDesc('id')
            ->limit(max(1, min($this->limit, 500)))
            ->get()
            ->map(fn (stdClass $event): array => $this->presentGeneric($event))
            ->values()
            ->all();
    }

    /** The event families of sign-ins and access decisions: «Безопасность» in the history, apart from the work done. */
    public const array SECURITY_PREFIXES = ['auth.', 'access.', 'admin_login_'];

    /**
     * The history's events (larena/admin «История», PX-50): the work done — every family the history shows less the
     * sign-ins and access decisions — or, with $security, those alone; newest first, at most $limit.
     *
     * @return list<array<string, mixed>>
     */
    public function history(bool $security = false, int $limit = 100): array
    {
        $query = $this->connection->table('larena_audit_events');
        if ($security) {
            $query->where(function ($query): void {
                foreach (self::SECURITY_PREFIXES as $prefix) {
                    $query->orWhereRaw('substr(event_type, 1, ?) = ?', [strlen($prefix), $prefix]);
                }
            });
        } else {
            $query->where(function ($query): void {
                $query->whereIn('event_type', self::FILE_EVENTS)
                    ->orWhereRaw('substr(event_type, 1, 8) = ?', ['storage.']);
                foreach ($this->sources as $source) {
                    $query->orWhereRaw('substr(event_type, 1, ?) = ?', [strlen($source['prefix']), $source['prefix']]);
                }
            });
            // Reads are not work done, and a decision is said once by its result.
            $query->whereRaw('substr(event_type, 1, 13) <> ?', ['storage.read.'])
                ->where('event_type', 'not like', '%.decision');
        }

        return $query->orderByDesc('id')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(fn (stdClass $event): array => $this->presentGeneric($event))
            ->values()
            ->all();
    }

    /** One event of the history by its id, read through the same allowlist; null when there is none. */
    public function find(int $id): ?array
    {
        $event = $id < 1 ? null : $this->connection->table('larena_audit_events')->where('id', $id)->first();

        return $event instanceof stdClass ? $this->presentGeneric($event) : null;
    }

    /** Whether an event type is a sign-in or an access decision. */
    public static function isSecurity(string $eventType): bool
    {
        foreach (self::SECURITY_PREFIXES as $prefix) {
            if (str_starts_with($eventType, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private const array FILE_EVENTS = ['file_uploaded', 'file_metadata_updated', 'file_deleted', 'file_used'];

    /** @return array<string, mixed> */
    private function presentGeneric(stdClass $event): array
    {
        $payload = $this->safePayload((string) $event->payload);
        $detail = [];
        foreach (['slug', 'status', 'version', 'role', 'operation', 'reason', 'logical_ref', 'display_name', 'mime_type', 'size_bytes', 'visibility', 'purpose', 'owner',
            // Who really acted while signed in as the event's actor (larena/audit AuditOnBehalf).
            \Larena\Audit\Contracts\AuditOnBehalf::FIELD] as $key) {
            $value = $this->allowedString($payload, $key);
            if ($value !== null) { $detail[$key] = $value; }
        }

        return [
            'id' => (int) $event->id, 'category' => (string) $event->category,
            'operation' => $this->operationLabel((string) $event->event_type),
            // The sentence a declaring package gives this event (its `words` group), for the history in words.
            'words' => $this->wordsKey((string) $event->event_type),
            'operation_code' => (string) $event->event_type, 'actor' => (string) $event->actor,
            'subject' => (string) $event->subject, 'detail' => $detail,
            'occurred_at' => (string) $event->occurred_at,
        ];
    }

    /** @return array<string, mixed> */
    private function safePayload(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $payload */
    private function allowedString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) || is_int($value) ? (string) $value : null;
    }

    /** The declared sentence of an event: `<words>.<type with dots as underscores>`, or null. */
    private function wordsKey(string $eventType): ?string
    {
        foreach ($this->sources as $source) {
            if (str_starts_with($eventType, $source['prefix']) && is_string($source['words'] ?? null) && $source['words'] !== '') {
                return $source['words'].'.'.str_replace('.', '_', $eventType);
            }
        }

        return null;
    }

    private function operationLabel(string $eventType): string
    {
        foreach ($this->sources as $source) {
            if (str_starts_with($eventType, $source['prefix'])) {
                return $source['label'];
            }
        }

        return match ($eventType) {
            'file_uploaded' => 'File uploaded',
            'file_metadata_updated' => 'File metadata updated',
            'file_deleted' => 'File deleted',
            'file_used' => 'File used on page',
            'storage.record.created' => 'Record created',
            'storage.record.updated' => 'Record updated',
            'storage.record.deleted' => 'Record archived',
            'storage.record.restored' => 'Record restored',
            'storage.record.purged' => 'Record purged',
            'storage.record.history_pruned' => 'Record history pruned',
            'storage.workbench.structure.archived' => 'Structure archived',
            'storage.workbench.structure.restored' => 'Structure restored',
            'storage.workbench.structure.purged' => 'Structure purged',
            'storage.workbench.structure.history_keep_changed' => 'Structure history limit changed',
            'storage.schema.created' => 'Schema created',
            'storage.schema.versioned' => 'Schema version created',
            'storage.schema.version_rejected' => 'Schema version rejected',
            'storage.publication.published' => 'Record published',
            'storage.publication.unpublished' => 'Record unpublished',
            'storage.publication.scheduled' => 'Publication scheduled',
            'storage.publication.archived' => 'Publication archived',
            'storage.publication.swept' => 'Scheduled publications applied',
            default => match (true) {
                str_starts_with($eventType, 'auth.'), str_starts_with($eventType, 'access.') => 'Security activity',
                str_starts_with($eventType, 'storage.') => 'Storage activity',
                default => 'Page activity',
            },
        };
    }
}
