<?php

declare(strict_types=1);

namespace Larena\Audit\Enums;

enum AuditSeverity: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Security = 'security';

    public function isSecurityRelevant(): bool
    {
        return in_array($this, [self::Critical, self::Security], true);
    }
}
