<?php

namespace App\Enums;

enum FleetStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Booked = 'booked';
    case Inactive = 'inactive';
}
