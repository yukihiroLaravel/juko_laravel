<?php

namespace App\Dto\Notification;

class PutDto
{
    public function __construct(
        private readonly int $notificationId,
        private readonly string $type,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly string $title,
        private readonly string $content,
        private readonly string $status
    ) {}
}
