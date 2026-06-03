<?php

declare(strict_types=1);

namespace Larena\Audit\Enums;

enum AuditRetentionClass: string
{
    case Ephemeral = 'ephemeral';
    case Operational = 'operational';
    case Security = 'security';
    case Commercial = 'commercial';
    case Legal = 'legal';
}
