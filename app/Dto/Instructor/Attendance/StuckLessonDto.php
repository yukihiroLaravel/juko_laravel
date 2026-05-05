<?php

namespace App\Dto\Instructor\Attendance;

readonly class StuckLessonDto
{
    public function __construct(
        public int $id,
        public string $title,
    ) {}
}
