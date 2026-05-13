<?php

namespace App\Enums\Chapter;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}