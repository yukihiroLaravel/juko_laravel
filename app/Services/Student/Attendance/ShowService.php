<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        $attendance = Attendance::with([
            'course.publicChapters.lessons' => fn ($q) => $q->where('status', LessonStatusEnum::PUBLIC->value),
            'course.instructor',
            'lessonAttendances',
            'course.courseDeadline',
        ])
            ->findOrFail($showDto->getAttendanceId());

        if ($attendance->student_id !== $showDto->getUserId()) {
            throw new AuthorizationException(
                "The attendance's student ID {$attendance->student_id} ".
                    "does not match User ID {$showDto->getUserId()}."
            );
        }

        if ($attendance->course?->status?->value !== CourseStatusEnum::PUBLIC->value) {
            throw new AuthorizationException('This course is not public.');
        }

        return $attendance;
    }
}
