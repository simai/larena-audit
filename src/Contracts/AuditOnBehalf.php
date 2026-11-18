<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

/**
 * Who really acts when a person acts in someone else's name: an administrator signed in as another person (owner
 * decision 2026-11-18). The package that knows it binds this (larena/auth); the history then says «Админ от имени
 * Имя» for every event written meanwhile. Without a binding nothing changes.
 */
interface AuditOnBehalf
{
    /** The payload field that keeps the real actor beside the event's actor, the person acted as. */
    public const string FIELD = 'signed_in_by';

    /** The real actor's reference when $actor is the person someone signed in as now; otherwise null. */
    public function realActor(string $actor): ?string;
}
