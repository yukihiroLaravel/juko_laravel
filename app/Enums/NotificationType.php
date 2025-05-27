<?php

namespace App\Enums;

enum NotificationType: string
{
    case ALWAYS = 'always';
    case ONCE = 'once';
}
