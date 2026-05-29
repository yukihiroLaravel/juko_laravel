<?php

namespace App\Enums\Chapter;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public static function switchableStatuses(): array
    {
        return [
            self::PUBLIC->value,
            self::PRIVATE->value,
        ];
    }
}
