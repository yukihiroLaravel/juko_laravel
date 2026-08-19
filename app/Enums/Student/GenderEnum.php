<?php

declare(strict_types=1);

namespace App\Enums\Student;

enum GenderEnum: string
{
    case UNKNOWN = 'unknown';
    case MAN = 'man';
    case WOMAN = 'woman';
}
