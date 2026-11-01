<?php

declare(strict_types=1);

namespace Larena\Audit\Tests\Fixtures\Names;

use Larena\Audit\Contracts\AuditReferenceNames;

/** A package that owns people: names the one it knows, and tries to name one nobody asked about. */
final class PeopleNames implements AuditReferenceNames
{
    public function names(array $refs): array
    {
        return array_filter([
            'user:admin_identity:1' => in_array('user:admin_identity:1', $refs, true) ? 'Anna Admin' : null,
            'user:admin_identity:99' => 'Not on the page',
        ], 'is_string');
    }
}
