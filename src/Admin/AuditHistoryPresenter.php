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
                'actor' => $this->onBehalfWords((string) $event['actor'], '', $names, is_array($event['detail'] ?? null) ? $event['detail'] : [],
                    $names[(string) $event['actor']] ?? (string) $event['actor']),
                'detail' => $this->detail(is_array($event['detail'] ?? null) ? $event['detail'] : []),
                'time' => (string) $event['occurred_at'],
            ], $events),
        ];
    }

    /**
     * The events in words for «История» (larena/admin, PX-50): what was done and over what, who did it — «Вы» for the
     * reader ($self), a person by name, or the system —, whether it was refused, and the codes apart for «Подробно».
     * Plain arrays: the admin turns them into its rows, so Audit needs no Admin class.
     *
     * @param list<array<string, mixed>> $events
     * @return list<array{key: string, title: string, at: string, actor: string, actor_key: string, object: string,
     *     object_key: string, outcome: string, note: string, security: bool, details: list<array{0: string, 1: string}>}>
     */
    public function entries(array $events, string $self = ''): array
    {
        $names = $this->names($events);
        $entries = [];
        foreach ($events as $event) {
            $type = (string) ($event['operation_code'] ?? '');
            $actor = (string) ($event['actor'] ?? '');
            $subject = (string) ($event['subject'] ?? '');
            $detail = is_array($event['detail'] ?? null) ? $event['detail'] : [];
            $object = $subject !== $actor ? ($names[$subject] ?? '') : '';
            $words = $this->words($type, is_string($event['words'] ?? null) ? $event['words'] : null, (string) ($event['operation'] ?? ''));
            $status = $detail['status'] ?? null;
            $denied = str_ends_with($type, '.denied') || str_ends_with($type, '_denied') || in_array($status, ['denied', 'capability_locked'], true);
            // A refused request (Core's `invalid` decision) did not happen: it is not a success.
            $failed = str_ends_with($type, '_failed') || str_ends_with($type, '.failed') || str_ends_with($type, '.rejected') || str_ends_with($type, '_rejected')
                || in_array($status, ['invalid', 'failed', 'rejected'], true);
            $details = [[$this->text('history.code'), $type]];
            if ($subject !== '') {
                $details[] = [$this->text('history.subject'), $subject];
            }
            if ($actor !== '') {
                $details[] = [$this->text('history.actor'), $actor];
            }
            foreach ($detail as $key => $value) {
                if (is_scalar($value) && (string) $value !== '') {
                    $details[] = [(string) $key, (string) $value];
                }
            }
            $entries[] = [
                'key' => (string) ($event['id'] ?? ''),
                'title' => $object !== '' ? $words.': '.$object : $words,
                'at' => self::moment((string) ($event['occurred_at'] ?? '')),
                'actor' => $this->onBehalfWords($actor, $self, $names, $detail),
                'actor_key' => $actor,
                'object' => $object,
                'object_key' => $object !== '' ? $subject : '',
                'outcome' => $denied ? 'denied' : ($failed ? 'failed' : 'done'),
                'note' => $denied ? $this->text('history.denied') : ($failed ? $this->text('history.failed') : ''),
                'security' => \Larena\Audit\ReadModel\AuditHistoryReader::isSecurity($type),
                'details' => $details,
            ];
        }

        return $entries;
    }

    /** The event's sentence: its package's, the history's own, or its family's name. */
    private function words(string $type, ?string $declared, string $label): string
    {
        foreach (array_filter([$declared, 'larena-audit::admin.history.events.'.str_replace('.', '_', $type)]) as $key) {
            $line = $this->translator->get($key);
            if (is_string($line) && $line !== $key && $line !== '') {
                return $line;
            }
        }

        return $label !== '' ? $this->operation($label) : $type;
    }

    /**
     * Who did it, and for an administrator signed in as another person «Админ от имени Имя» (owner decision
     * 2026-11-18): the real actor the event's payload keeps (AuditOnBehalf), then the person acted as.
     *
     * @param array<string, string> $names
     * @param array<string, mixed> $detail
     */
    private function onBehalfWords(string $actor, string $self, array $names, array $detail, ?string $said = null): string
    {
        $as = $said ?? $this->actorWords($actor, $self, $names);
        $real = $detail[\Larena\Audit\Contracts\AuditOnBehalf::FIELD] ?? null;
        if (!is_string($real) || $real === '' || $real === $actor) {
            return $as;
        }

        return $this->text('history.on_behalf', ['real' => $this->actorWords($real, $self, $names), 'as' => $names[$actor] ?? $as]);
    }

    /** @param array<string, string> $names */
    private function actorWords(string $actor, string $self, array $names): string
    {
        if ($actor !== '' && $actor === $self) {
            return $this->text('history.you');
        }
        if (isset($names[$actor])) {
            return $names[$actor];
        }

        return $this->text(str_starts_with($actor, 'user:') ? 'history.someone' : 'history.system');
    }

    /** The stored UTC moment (`Y-m-d H:i:s`) as ISO 8601, for the reader's own time; empty when it is not one. */
    private static function moment(string $stored): string
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}\z/D', $stored) === 1) {
            return str_replace(' ', 'T', $stored).'Z';
        }

        return preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:?\d{2})\z/D', $stored) === 1 ? $stored : '';
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
            $real = is_array($event['detail'] ?? null) ? ($event['detail'][\Larena\Audit\Contracts\AuditOnBehalf::FIELD] ?? null) : null;
            if (is_string($real) && $real !== '') {
                $refs[$real] = true;
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

    /** @param array<string, string> $replace */
    private function text(string $key, array $replace = []): string
    {
        return (string) $this->translator->get('larena-audit::admin.' . $key, $replace);
    }
}
