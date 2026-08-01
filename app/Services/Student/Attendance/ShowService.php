<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Model\Attendance;

class ShowService
{
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        return Attendance::with([
            'course.publicChapters.publicLessons',
            'course.instructor',
            'lessonAttendances',
            'course.courseDeadline',
        ])
            ->findOrFail($showDto->getAttendanceId());
    }
}
