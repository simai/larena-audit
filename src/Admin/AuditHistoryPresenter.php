<?php

declare(strict_types=1);

namespace Larena\Audit\Admin;

use Illuminate\Contracts\Translation\Translator;
use Larena\Audit\Contracts\AuditReferenceNames;
use Throwable;

final readonly class AuditHistoryPresenter
{
    /** @param list<AuditReferenceNames> $names what the declaring packages call the actors and subjects they own */
    public function __construct(private Translator $translator, private array $names = []) {}

    /**
     * The empty state as a neutral node, or null when there are events to list; the admin shell renders it
     * (larena-admin::components.nodes).
     *
     * @param list<array<string, mixed>> $events
     * @return array{component: string, props: array<string, string>}|null
     */
    public function empty(array $events): ?array
    {
        return $events === [] ? ['component' => 'sf-alert', 'props' => [
            'status' => 'info',
            'heading' => $this->text('empty_title'),
            'supporting-text' => $this->text('empty_description'),
        ]] : null;
    }

    /**
     * The events as a plain list description; the admin shell turns it into its shared
     * data view (larena.admin-dataview-page), so Audit needs no Admin class.
     *
     * @param list<array<string, mixed>> $events
     * @return array<string, mixed>
     */
    public function list(array $events): array
    {
        $names = $this->names($events);

        return [
            'id' => 'audit-history',
            'title' => $this->text('region_label'),
            'page_id' => 'page.admin.audit-history',
            'owner' => 'larena/audit',
            // An event opens as its record, over the admin's history port, from its operation.
            'record' => ['collection' => 'audit.events', 'port' => '/admin/audit/record-port', 'scope' => 'scope:audit'],
            'columns' => [
                ['key' => 'operation', 'label' => $this->text('columns.operation'), 'role' => 'title'],
                ['key' => 'subject', 'label' => $this->text('columns.subject')],
                ['key' => 'actor', 'label' => $this->text('columns.actor')],
                ['key' => 'detail', 'label' => $this->text('columns.detail')],
                // Kept in UTC; the admin shows it in the site's time zone.
                ['key' => 'time', 'label' => $this->text('columns.time'), 'type' => 'moment'],
            ],
            'rows' => array_map(fn (array $event): array => [
                'id' => 'event-' . (string) ($event['id'] ?? ''),
                'operation' => $this->operation((string) $event['operation']) . ' · ' . (string) $event['operation_code'],
                'subject' => $names[(string) $event['subject']] ?? (string) $event['subject'],
                'actor' => $names[(string) $event['actor']] ?? (string) $event['actor'],
                'detail' => $this->detail(is_array($event['detail'] ?? null) ? $event['detail'] : []),
                'time' => (string) $event['occurred_at'],
            ], $events),
        ];
    }

    /**
     * The people and things on the page by name; a namer that fails or answers for a reference it was not asked
     * about is ignored, so the history still lists every event.
     *
     * @param list<array<string, mixed>> $events
     * @return array<string, string>
     */
    private function names(array $events): array
    {
        $refs = [];
        foreach ($events as $event) {
            foreach (['actor', 'subject'] as $key) {
                $ref = (string) ($event[$key] ?? '');
                if ($ref !== '') {
                    $refs[$ref] = true;
                }
            }
        }
        $refs = array_keys($refs);
        $names = [];
        foreach ($refs === [] ? [] : $this->names as $namer) {
            try {
                $answer = $namer->names(array_map('strval', $refs));
            } catch (Throwable) {
                continue;
            }
            foreach ($answer as $ref => $name) {
                $name = trim($name);
                if (!isset($names[$ref]) && in_array($ref, $refs, true) && $name !== '') {
                    $names[$ref] = $name;
                }
            }
        }

        return $names;
    }

    /** A declared family's label is its package's own translation key; the rest are the history's own words. */
    private function operation(string $label): string
    {
        return str_contains($label, '::') ? (string) $this->translator->get($label) : $this->text('operations.' . $label);
    }

    /** @param array<string, mixed> $detail */
    private function detail(array $detail): string
    {
        if ($detail === []) {
            return '—';
        }

        $parts = [];
        foreach ($detail as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = (string) $key . ': ' . ($key === 'slug' ? '/' : '') . (string) $value;
            }
        }

        return $parts === [] ? '—' : implode(' · ', $parts);
    }

    private function text(string $key): string
    {
        return (string) $this->translator->get('larena-audit::admin.' . $key);
    }
}
