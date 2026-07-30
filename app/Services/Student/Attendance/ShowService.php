<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Builder;

class ShowService
{
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        $attendance = Attendance::with([
            'course.publicChapters.lessons' => fn(Builder $query) => $query->public(),
            'course.instructor',
            'lessonAttendances',
            'course.courseDeadline',
        ])
            ->findOrFail($showDto->getAttendanceId());

        return $attendance;
    }
}
