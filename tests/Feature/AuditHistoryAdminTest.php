<?php

declare(strict_types=1);

namespace Larena\Audit\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Audit\Tests\TestCase;

final class AuditHistoryAdminTest extends TestCase
{
    public function testEmptyHistoryHasUsefulState(): void
    {
        $this->get('/admin/audit')
            ->assertOk()
            ->assertSee('Audit history')
            ->assertSee('No Page activity yet')
            ->assertSee('data-larena-audit-empty', false);
    }

    public function testNewestPageEventsRenderThroughSafeAllowlist(): void
    {
        $this->insertEvent(1, 'docara_page_created', [
            'slug' => 'welcome',
            'status' => 'draft',
            'version' => '1',
            'body' => 'TOP SECRET BODY',
            'token' => 'do-not-render',
            'unknown' => '<script>unsafe</script>',
        ]);
        $this->insertEvent(2, 'docara_page_published', [
            'slug' => '<welcome>',
            'status' => 'published',
            'version' => 2,
        ]);
        $this->insertEvent(3, 'unrelated_event', ['slug' => 'not-visible'], 'larena/auth', 'identity');

        $response = $this->get('/admin/audit');

        $response->assertOk()
            ->assertSee('data-larena-audit-history="persistent"', false)
            ->assertDontSee(' read-only=', false)
            ->assertSee('selectable="false"', false)
            ->assertSee('settings="false"', false)
            ->assertSee('actions="false"', false)
            ->assertSeeInOrder(['Published', 'Created'])
            ->assertSee('\u003Cwelcome\u003E', false)
            ->assertSee('user:admin_identity:1')
            ->assertSee('published')
            ->assertDontSee('TOP SECRET BODY')
            ->assertDontSee('do-not-render')
            ->assertDontSee('unsafe')
            ->assertDontSee('not-visible')
            ->assertDontSee('"payload"');

        self::assertSame(3, DB::table('larena_audit_events')->count());
    }

    public function testDefaultRouteContractRequiresAuthenticatedAdministrator(): void
    {
        /** @var array<string, mixed> $config */
        $config = require __DIR__ . '/../../config/larena-audit.php';

        self::assertSame([
            'web',
            'larena-auth.entry',
            'larena-auth.admin-required',
            'larena-admin.locale',
            'access:audit.history.read',
        ], $config['admin']['middleware']);
        self::assertSame(['local', 'testing'], $config['admin']['allowed_environments']);
    }

    public function testSecurityLifecycleEventsUseSafeProjection(): void
    {
        $this->insertEvent(1, 'auth.user.created', [
            'role' => 'editor', 'password' => 'must-not-render', 'password_hash' => 'must-not-render-either',
        ], 'larena/auth', 'identity_lifecycle');

        $this->get('/admin/audit')->assertOk()
            ->assertSee('Security activity')
            ->assertSee('auth.user.created')
            ->assertSee('role')
            ->assertSee('editor')
            ->assertDontSee('must-not-render');
    }

    public function testHistorySurfaceHasRussianTranslationParity(): void
    {
        $this->app->setLocale('ru');

        $this->get('/admin/audit')
            ->assertOk()
            ->assertSee('История действий')
            ->assertSee('Действий со страницами пока нет');
    }

    /** @param array<string, mixed> $payload */
    private function insertEvent(
        int $id,
        string $eventType,
        array $payload,
        string $sourcePackage = 'larena/docara',
        string $category = 'content_authoring',
    ): void {
        DB::table('larena_audit_events')->insert([
            'id' => $id,
            'source_package' => $sourcePackage,
            'category' => $category,
            'event_type' => $eventType,
            'actor' => 'user:admin_identity:1',
            'subject' => 'docara:page:1',
            'severity' => 'notice',
            'retention_class' => 'operational',
            'correlation_id' => 'correlation-' . $id,
            'occurred_at' => '2026-07-10 08:0' . $id . ':00',
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => '2026-07-10 08:0' . $id . ':00',
        ]);
    }
}
