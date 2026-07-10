<?php

declare(strict_types=1);

namespace Larena\Audit\Navigation;

use Larena\Admin\Contracts\AdminNavigationContributor;
use Larena\Admin\Navigation\AdminNavigationDescriptor;

final class AuditAdminNavigationContributor implements AdminNavigationContributor
{
    public function ownerPackage(): string
    {
        return 'larena/audit';
    }

    public function navigationDescriptors(): array
    {
        return [new AdminNavigationDescriptor(
            id: 'audit.history',
            ownerPackage: $this->ownerPackage(),
            label: 'Audit history',
            routeName: 'larena.audit.admin.index',
            routeUri: '/admin/audit',
            category: 'operations',
            state: 'product_available',
            accessScope: 'audit.history.read',
            auditEvent: 'audit.history.viewed',
            statusCap: 'post_beta_content_site_assembly',
            order: 90,
            group: 'operations',
            badge: null,
            knownLimitations: ['local_testing_only', 'not_production_ready'],
            surface: 'product',
            labelKey: 'larena-audit::admin.navigation.history',
            activeRoutePattern: 'larena.audit.admin.*',
        )];
    }
}
