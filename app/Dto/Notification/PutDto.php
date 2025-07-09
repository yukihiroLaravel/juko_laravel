<?php

namespace App\Dto\Notification;

class PutDto
{
    public function __construct(
        public readonly string $type,
        public readonly string $start_date,
        public readonly string $end_date,
        public readonly string $title,
        public readonly string $content,
        public readonly string $status
    ) {}
}
