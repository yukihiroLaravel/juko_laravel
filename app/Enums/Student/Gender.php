<?php

declare(strict_types=1);

namespace App\Enums\Student;

enum Gender: string
{
    case UNKNOWN = 'unknown';
    case MAN = 'man';
    case WOMAN = 'woman';
}
