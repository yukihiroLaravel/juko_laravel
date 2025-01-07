<?php

declare(strict_types=1);

namespace App\Enums\Student;

enum Gender: int
{
    case UNKNOWN = 0;
    case MAN = 1;
    case WOMAN = 2;

    public function label(): string
    {
        return match($this) {
            self::MAN => 'man',
            self::WOMAN => 'woman',
            self::UNKNOWN => 'unknown',
        };
    }
}