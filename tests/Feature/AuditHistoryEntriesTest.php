<?php

declare(strict_types=1);

namespace Larena\Audit\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Larena\Audit\Admin\AuditHistoryPresenter;
use Larena\Audit\ReadModel\AuditHistoryReader;
use Larena\Audit\Tests\TestCase;

/**
 * The journal's events in the admin's «История» (owner decision dec-d9f352569fd1, PX-50): the work done and, apart, the
 * sign-ins and refusals of access; each said in words, the codes under «Подробно» only; the old address leads to it.
 */
final class AuditHistoryEntriesTest extends TestCase
{
    public function testWorkAndSecurityAreReadApart(): void
    {
        $this->insertEvent(1, 'storage.record.created', ['operation' => 'create'], 'larena/storage', 'storage_record');
        $this->insertEvent(2, 'access.operation.denied', ['status' => 'denied'], 'larena/access', 'access');
        $this->insertEvent(3, 'storage.read.record', [], 'larena/storage', 'storage_record');
        $this->insertEvent(4, 'admin_login_succeeded', [], 'larena/auth', 'identity');
        $this->insertEvent(5, 'docara_page_published', ['slug' => 'welcome']);
        $this->insertEvent(6, 'access.storage.decision', [], 'larena/access', 'access');

        $reader = $this->app->make(AuditHistoryReader::class);
        self::assertSame(['docara_page_published', 'storage.record.created'], array_column($reader->history(), 'operation_code'));
        self::assertSame(['access.storage.decision', 'admin_login_succeeded', 'access.operation.denied'], array_column($reader->history(true), 'operation_code'));
        self::assertCount(1, $reader->history(false, 1));
        self::assertSame('storage.record.created', $reader->find(1)['operation_code'] ?? null);
        self::assertNull($reader->find(99));
        self::assertNull($reader->find(0));
        self::assertTrue(AuditHistoryReader::isSecurity('auth.user.created'));
        self::assertFalse(AuditHistoryReader::isSecurity('storage.record.created'));
    }

    public function testEventsAreSaidInWordsWithTheCodesApart(): void
    {
        $this->app->setLocale('ru');
        $this->insertEvent(1, 'storage.record.created', ['operation' => 'create', 'version' => 2], 'larena/storage', 'storage_record');
        $this->insertEvent(2, 'access.operation.denied', ['status' => 'denied'], 'larena/access', 'access', 'user:admin_identity:2');
        $this->insertEvent(3, 'storage.schema.version_rejected', ['reason' => 'incompatible'], 'larena/storage', 'storage_schema', 'system:installer');

        $reader = $this->app->make(AuditHistoryReader::class);
        $presenter = $this->app->make(AuditHistoryPresenter::class);
        $entries = $presenter->entries([...$reader->history(), ...$reader->history(true)], 'user:admin_identity:1');
        $of = static function (string $key) use ($entries): array {
            foreach ($entries as $entry) {
                if ($entry['key'] === $key) {
                    return $entry;
                }
            }
            self::fail('No entry '.$key);
        };

        $created = $of('1');
        self::assertSame('Создана запись', $created['title']);
        self::assertSame('Вы', $created['actor']);
        self::assertSame('done', $created['outcome']);
        self::assertSame('2026-07-10T08:01:00Z', $created['at']);
        self::assertFalse($created['security']);
        self::assertContains(['Операция', 'storage.record.created'], $created['details']);
        self::assertStringNotContainsString('storage.record', $created['title']);

        $denied = $of('2');
        self::assertSame('denied', $denied['outcome']);
        self::assertTrue($denied['security']);
        self::assertSame('Отказано в доступе', $denied['title']);
        self::assertNotSame('', $denied['note']);

        $rejected = $of('3');
        self::assertSame('failed', $rejected['outcome']);
        self::assertNotSame('user:admin_identity:1', $rejected['actor']);
    }

    /**
     * An administrator signed in as another person (owner decision 2026-11-18): every event written meanwhile keeps the
     * person as its actor and the real administrator beside it, and the history says «Админ от имени Имя».
     */
    public function testAnActionInSomeoneElsesNameNamesBothPeople(): void
    {
        $this->app->setLocale('ru');
        $sink = new \Larena\Audit\Sinks\DatabaseAuditSink(DB::connection(), new class implements \Larena\Audit\Contracts\AuditOnBehalf {
            public function realActor(string $actor): ?string
            {
                return $actor === 'user:admin_identity:7' ? 'user:admin_identity:1' : null;
            }
        });
        $event = static fn (string $actor): \Larena\Audit\Contracts\AuditEvent => \Larena\Audit\Contracts\AuditEvent::create(
            sourcePackage: 'larena/storage', category: 'storage_record', type: 'storage.record.updated', actor: $actor,
            subject: 'storage:record:5', severity: \Larena\Audit\Enums\AuditSeverity::Info,
            retentionClass: \Larena\Audit\Enums\AuditRetentionClass::Operational, correlationId: 'c-'.$actor,
            occurredAt: new \DateTimeImmutable('2026-11-18 12:00:00'), payload: ['operation' => 'update'],
        );
        $sink->write($event('user:admin_identity:7'));
        $sink->write($event('user:admin_identity:3'));
        $payloads = DB::table('larena_audit_events')->orderBy('id')->pluck('payload')->map(static fn (string $payload): array => json_decode($payload, true))->all();
        self::assertSame('user:admin_identity:1', $payloads[0][\Larena\Audit\Contracts\AuditOnBehalf::FIELD] ?? null);
        self::assertArrayNotHasKey(\Larena\Audit\Contracts\AuditOnBehalf::FIELD, $payloads[1]);

        $names = new class implements \Larena\Audit\Contracts\AuditReferenceNames {
            public function names(array $refs): array
            {
                return array_intersect_key(['user:admin_identity:1' => 'Главный Админ', 'user:admin_identity:7' => 'Тест Вход'], array_flip($refs));
            }
        };
        $presenter = new AuditHistoryPresenter($this->app->make('translator'), [$names]);
        $entries = $presenter->entries($this->app->make(AuditHistoryReader::class)->history());
        self::assertSame('Главный Админ от имени Тест Вход', $entries[1]['actor']);
        self::assertSame('Вы от имени Тест Вход', $presenter->entries($this->app->make(AuditHistoryReader::class)->history(), 'user:admin_identity:1')[1]['actor']);
        self::assertSame('Другой пользователь', $entries[0]['actor']);
    }

    public function testEveryHistorySentenceHasBothLanguages(): void
    {
        /** @var array<string, mixed> $ru */
        $ru = require __DIR__.'/../../resources/lang/ru/admin.php';
        /** @var array<string, mixed> $en */
        $en = require __DIR__.'/../../resources/lang/en/admin.php';
        self::assertIsArray($ru['history']['events'] ?? null);
        self::assertSame(array_keys($ru['history']['events']), array_keys($en['history']['events']));
        self::assertSame(array_keys($ru['history']), array_keys($en['history']));
    }

    public function testTheOldAddressLeadsToTheAdminHistory(): void
    {
        Route::get('/admin/history', static fn (): string => 'history')->name('larena.admin.history.index');
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->get('/admin/audit')->assertRedirect('/admin/history');
    }

    /** @param array<string, mixed> $payload */
    private function insertEvent(
        int $id,
        string $eventType,
        array $payload,
        string $sourcePackage = 'larena/docara',
        string $category = 'content_authoring',
        string $actor = 'user:admin_identity:1',
    ): void {
        DB::table('larena_audit_events')->insert([
            'id' => $id,
            'source_package' => $sourcePackage,
            'category' => $category,
            'event_type' => $eventType,
            'actor' => $actor,
            'subject' => 'docara:page:1',
            'severity' => 'notice',
            'retention_class' => 'operational',
            'correlation_id' => 'correlation-'.$id,
            'occurred_at' => '2026-07-10 08:0'.$id.':00',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => '2026-07-10 08:0'.$id.':00',
        ]);
    }
}
