<?php

namespace App\Enums\Lesson;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}