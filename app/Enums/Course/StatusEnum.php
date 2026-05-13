<?php

namespace App\Enums\Course;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}
