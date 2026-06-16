<?php

namespace App\Enums;

enum Surface: string
{
    case Asphalt = 'ASPHALT';
    case Hardpacked = 'HARDPACKED';
    case Mixed = 'MIXED';
    case Soft = 'SOFT';
    case Mud = 'MUD';
}
