<?php

declare(strict_types=1);

namespace Larena\Audit\Http\Controllers;

use Illuminate\Contracts\View\View;
use Larena\Audit\Admin\AuditHistoryPresenter;
use Larena\Audit\ReadModel\AuditHistoryReader;

final readonly class AuditHistoryAdminController
{
    public function __invoke(AuditHistoryReader $history, AuditHistoryPresenter $presenter): View
    {
        $events = $history->events();
        return view('larena-audit::admin.index', [
            'events' => $events,
            'historyUi' => $presenter->present($events),
        ]);
    }
}
