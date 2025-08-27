<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Model\Attendance;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        $attendance = Attendance::with([
            'course.publicChapters.lessons',
            'course.instructor',
            'lessonAttendances',
            'course.deadline',
        ])
            ->findOrFail($showDto->getAttendanceId());

        if ($attendance->student_id !== $showDto->getUserId()) {
            throw new AuthorizationException(
                "The attendance's student ID {$attendance->student_id} ".
                "does not match User ID {$showDto->getUserId()}."
            );
        }

        return $attendance;
    }
}
