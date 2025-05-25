<?php

namespace App\Enums\Instructor\Notification;

enum NotificationStatus: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}
