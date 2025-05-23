<?php

namespace App\Enums\Instructor;

enum Status: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public static function values(): array
    {
        return array_column(Status::cases(), 'value');
    }
}
