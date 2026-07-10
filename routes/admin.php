<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larena\Audit\Http\Controllers\AuditHistoryAdminController;

Route::prefix((string) config('larena-audit.admin.prefix', 'admin/audit'))
    ->middleware((array) config('larena-audit.admin.middleware', []))
    ->name('larena.audit.admin.')
    ->group(static function (): void {
        Route::get('/', AuditHistoryAdminController::class)->name('index');
    });
