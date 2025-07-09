<?php

namespace App\Enums\Notification;

enum FilterEnum: string
{
    case READ = 'read';
    case UNREAD = 'unread';
}
