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
        ],
        'limit' => 100,
    ],
];
