<?php

namespace App\Dto\Common\Attendance;

readonly class StuckLessonDto
{
    public function __construct(
        public int $id,
        public string $title,
    ) {}
}
