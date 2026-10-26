<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canManage(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }
}
