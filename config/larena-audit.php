<?php

declare(strict_types=1);

$adminRoutes = getenv('LARENA_AUDIT_ADMIN_ROUTES');

return [
    'admin' => [
        'enabled' => $adminRoutes === false
            ? true
            : filter_var($adminRoutes, FILTER_VALIDATE_BOOL),
        'allowed_environments' => ['local', 'testing'],
        'prefix' => 'admin/audit',
        'middleware' => [
            'web',
            'larena-auth.entry',
            'larena-auth.admin-required',
            'larena-admin.locale',
            'access:audit.history.read',
        ],
        'limit' => 100,
    ],
    // Event families other packages show in the history: each declares {prefix: 'env.', label: '<package>::<key>'}
    // by pushing onto this list in its provider's boot, so the audit package needs to know none of them.
    'history_sources' => [],
];
