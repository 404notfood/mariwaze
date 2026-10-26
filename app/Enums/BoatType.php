<?php

namespace App\Enums;

enum BoatType: string
{
    case Sailboat = 'voilier';
    case Catamaran = 'catamaran';
    case Motorboat = 'moteur';
    case Dinghy = 'deriveur';
}
