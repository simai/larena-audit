<?php

declare(strict_types=1);

namespace Larena\Audit\Tests\Feature;

use Illuminate\Database\DatabaseManager;
use Larena\Audit\Contracts\AuditSink;
use Larena\Audit\Contracts\ConnectionBoundAuditEventPipeline;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Audit\Runtime\DatabaseAuditEventPipeline;
use Larena\Audit\Runtime\DefaultAuditRedactor;
use Larena\Audit\Sinks\DatabaseAuditSink;
use Larena\Audit\Tests\TestCase;
use ReflectionProperty;

final class DatabaseAuditEventPipelineBindingTest extends TestCase
{
    public function testProviderBindsProofPipelineToExactDefaultConnection(): void
    {
        /** @var DatabaseManager $database */
        $database = $this->app->make(DatabaseManager::class);
        $defaultConnection = $database->connection();

        $contract = $this->app->make(ConnectionBoundAuditEventPipeline::class);
        $concrete = $this->app->make(DatabaseAuditEventPipeline::class);

        self::assertInstanceOf(DatabaseAuditEventPipeline::class, $contract);
        self::assertSame($defaultConnection, $contract->connection());
        self::assertSame($defaultConnection, $concrete->connection());
        self::assertSame(
            DatabaseAuditEventPipeline::class,
            $this->app->getAlias(ConnectionBoundAuditEventPipeline::class),
        );
    }

    public function testExistingGenericBindingsRemainDatabaseBackedAndUnchanged(): void
    {
        /** @var DatabaseManager $database */
        $database = $this->app->make(DatabaseManager::class);
        $defaultConnection = $database->connection();

        $concreteSink = $this->app->make(DatabaseAuditSink::class);
        $contractSink = $this->app->make(AuditSink::class);
        $genericPipeline = $this->app->make(AuditEventPipeline::class);

        self::assertInstanceOf(DatabaseAuditSink::class, $concreteSink);
        self::assertInstanceOf(DatabaseAuditSink::class, $contractSink);

        $sinkConnection = new ReflectionProperty(DatabaseAuditSink::class, 'connection');
        self::assertSame($defaultConnection, $sinkConnection->getValue($concreteSink));
        self::assertSame($defaultConnection, $sinkConnection->getValue($contractSink));

        $redactor = new ReflectionProperty(AuditEventPipeline::class, 'redactor');
        self::assertInstanceOf(DefaultAuditRedactor::class, $redactor->getValue($genericPipeline));

        $sinks = new ReflectionProperty(AuditEventPipeline::class, 'sinks');
        $genericSinks = $sinks->getValue($genericPipeline);
        self::assertIsArray($genericSinks);
        self::assertCount(1, $genericSinks);
        self::assertInstanceOf(DatabaseAuditSink::class, $genericSinks[0]);
        self::assertSame($defaultConnection, $sinkConnection->getValue($genericSinks[0]));
    }
}
