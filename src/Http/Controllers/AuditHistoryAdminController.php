<?php

declare(strict_types=1);

namespace Larena\Audit\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Larena\Audit\Admin\AuditHistoryPresenter;
use Larena\Audit\ReadModel\AuditHistoryReader;

final readonly class AuditHistoryAdminController
{
    /** The admin's «История» (larena/admin, owner decision dec-d9f352569fd1): where this history is shown in words. */
    public const string HISTORY_ROUTE = 'larena.admin.history.index';

    public function __invoke(AuditHistoryReader $history, AuditHistoryPresenter $presenter, Router $router, Request $request): View|RedirectResponse
    {
        // With the admin's «История» the events are shown there, in words, beside the runs; this address leads to it. An
        // event's record panel, asked by its part, still answers here.
        if ($router->has(self::HISTORY_ROUTE) && !$request->headers->has('X-Larena-Fragment') && !$request->query->has('record_id')) {
            return new RedirectResponse(route(self::HISTORY_ROUTE, [], false));
        }
        $events = $history->events();
        return view('larena-audit::admin.index', [
            'events' => $events,
            'emptyUi' => $presenter->empty($events),
            'historyList' => $presenter->list($events),
        ]);
    }
}
