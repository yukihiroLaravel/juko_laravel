<?php

namespace App\Dto\Student\Attendance;

class CourseProgressDto
{
    public function __construct(
        private readonly int $completedChaptersCount,
        private readonly int $totalChaptersCount,
        private readonly int $completedLessonsCount,
        private readonly int $totalLessonsCount
    ) {}

    public function getCompletedChaptersCount(): int
    {
        return $this->completedChaptersCount;
    }

    public function getTotalChaptersCount(): int
    {
        return $this->totalChaptersCount;
    }

    public function getCompletedLessonsCount(): int
    {
        return $this->completedLessonsCount;
    }

    public function getTotalLessonsCount(): int
    {
        return $this->totalLessonsCount;
    }
}
