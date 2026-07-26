<?php

declare(strict_types=1);

namespace Larena\Audit\ReadModel;

use Illuminate\Database\ConnectionInterface;
use JsonException;
use stdClass;

final readonly class AuditHistoryReader
{
    public function __construct(
        private ConnectionInterface $connection,
        private int $limit = 100,
    ) {
    }

    /**
     * @return list<array{
     *     id:int,
     *     operation:string,
     *     actor:string,
     *     subject:string,
     *     slug:string|null,
     *     status:string|null,
     *     version:string|null,
     *     occurred_at:string
     * }>
     */
    public function pageEvents(): array
    {
        return $this->connection->table('larena_audit_events')
            ->where('source_package', 'larena/docara')
            ->where('category', 'content_authoring')
            ->orderByDesc('id')
            ->limit(max(1, min($this->limit, 500)))
            ->get()
            ->map(fn (stdClass $event): array => $this->present($event))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        return $this->connection->table('larena_audit_events')
            ->where(static function ($query): void {
                $query->whereIn('event_type', [
                    'docara_page_created', 'docara_page_updated', 'docara_page_published',
                    'docara_page_unpublished', 'docara_page_submitted_for_review',
                    'docara_page_restored', 'docara_page_update_denied',
                    'file_uploaded', 'file_metadata_updated', 'file_deleted', 'file_used',
                ])->orWhere('event_type', 'like', 'auth.%')
                    ->orWhere('event_type', 'like', 'access.%');
            })
            ->orderByDesc('id')
            ->limit(max(1, min($this->limit, 500)))
            ->get()
            ->map(fn (stdClass $event): array => $this->presentGeneric($event))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function presentGeneric(stdClass $event): array
    {
        $payload = $this->safePayload((string) $event->payload);
        $detail = [];
        foreach (['slug', 'status', 'version', 'role', 'operation', 'reason', 'logical_ref', 'display_name', 'mime_type', 'size_bytes', 'visibility', 'purpose'] as $key) {
            $value = $this->allowedString($payload, $key);
            if ($value !== null) { $detail[$key] = $value; }
        }

        return [
            'id' => (int) $event->id, 'category' => (string) $event->category,
            'operation' => $this->operationLabel((string) $event->event_type),
            'operation_code' => (string) $event->event_type, 'actor' => (string) $event->actor,
            'subject' => (string) $event->subject, 'detail' => $detail,
            'occurred_at' => (string) $event->occurred_at,
        ];
    }

    /**
     * The persistent payload is deliberately projected through an allowlist.
     * Raw payload, content body and unknown fields never reach the view.
     *
     * @return array{id:int,operation:string,actor:string,subject:string,slug:string|null,status:string|null,version:string|null,occurred_at:string}
     */
    private function present(stdClass $event): array
    {
        $payload = $this->safePayload((string) $event->payload);

        return [
            'id' => (int) $event->id,
            'operation' => $this->operationLabel((string) $event->event_type),
            'actor' => (string) $event->actor,
            'subject' => (string) $event->subject,
            'slug' => $this->allowedString($payload, 'slug'),
            'status' => $this->allowedString($payload, 'status'),
            'version' => $this->allowedString($payload, 'version'),
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

    private function operationLabel(string $eventType): string
    {
        return match ($eventType) {
            'docara_page_created' => 'Created',
            'docara_page_updated' => 'Updated',
            'docara_page_published' => 'Published',
            'docara_page_unpublished' => 'Unpublished',
            'docara_page_submitted_for_review' => 'Submitted for publication',
            'docara_page_restored' => 'Restored',
            'docara_page_update_denied' => 'Permission denied',
            'file_uploaded' => 'File uploaded',
            'file_metadata_updated' => 'File metadata updated',
            'file_deleted' => 'File deleted',
            'file_used' => 'File used on page',
            default => str_starts_with($eventType, 'auth.') || str_starts_with($eventType, 'access.')
                ? 'Security activity'
                : 'Page activity',
        };
    }
}
