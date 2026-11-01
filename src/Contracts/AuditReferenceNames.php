<?php

declare(strict_types=1);

namespace Larena\Audit\Contracts;

/**
 * Names the history shows for the references a package owns (an actor or a subject such as
 * "user:admin_identity:7"). A package declares its class in `larena-audit.reference_names` from its provider's boot;
 * the history keeps the raw reference for anything no package names.
 */
interface AuditReferenceNames
{
    /**
     * @param list<string> $refs the references on the page, each once
     * @return array<string, string> a readable name for each reference this package owns and can name
     */
    public function names(array $refs): array;
}
