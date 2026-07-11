<?php

declare(strict_types=1);

namespace Larena\Audit\Admin;

use Illuminate\Contracts\Translation\Translator;
use Larena\Ui\Smart;

final readonly class AuditHistoryPresenter
{
    public function __construct(private Translator $translator) {}

    /** @param list<array<string, mixed>> $events */
    public function present(array $events): string
    {
        if ($events === []) {
            return Smart::render('sf-alert', [
                'type' => 'info',
                'title' => $this->text('empty_title'),
                'supporting-text' => $this->text('empty_description'),
            ])->html;
        }

        $rows = array_map(fn (array $event): array => [
            'operation' => $this->text('operations.' . (string) $event['operation']) . ' · ' . (string) $event['operation_code'],
            'subject' => (string) $event['subject'],
            'actor' => (string) $event['actor'],
            'detail' => $this->detail(is_array($event['detail'] ?? null) ? $event['detail'] : []),
            'time' => (string) $event['occurred_at'],
        ], $events);

        return Smart::render('sf-table', [
            'aria-label' => $this->text('region_label'),
            'read-only' => 'true',
            'data' => [
                'columns' => [
                    ['key' => 'operation', 'label' => $this->text('columns.operation')],
                    ['key' => 'subject', 'label' => $this->text('columns.subject')],
                    ['key' => 'actor', 'label' => $this->text('columns.actor')],
                    ['key' => 'detail', 'label' => $this->text('columns.detail')],
                    ['key' => 'time', 'label' => $this->text('columns.time')],
                ],
                'rows' => $rows,
            ],
        ])->html;
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
