<?php

declare(strict_types=1);

namespace Larena\Audit\Install;

use Larena\Core\Contracts\InstallAuditTrailAdapter;

/**
 * The install audit trail for Core installs: Core owns the contract, Audit records the trail. Registered by
 * AuditServiceProvider only when Core is installed; without Audit, Core keeps its safe behaviour.
 */
final class CoreInstallAuditTrailAdapter implements InstallAuditTrailAdapter
{
    public function migrationPath(): string
    {
        return InstallAuditTrail::migrationPath();
    }

    public function plannedTables(): array
    {
        return InstallAuditTrail::plannedTables();
    }

    public function eventPayload(array $launchRecord, string $step, string $status, array $context): array
    {
        return InstallAuditTrail::eventPayload($launchRecord, $step, $status, $context);
    }
}
