<?php

namespace App\Enums\Course;

enum DeadlineTypeEnum: string
{
    case NONE = 'none';
    case FIXED_DATE = 'fixed_date';
    case RELATIVE_DAYS = 'relative_days';
}
