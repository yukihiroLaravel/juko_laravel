<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShowService
{
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        return Attendance::with([
            'course.publicChapters.lessons' => fn (Relation $query) => $query->where('status', LessonStatusEnum::PUBLIC->value),
            'course.instructor',
            'lessonAttendances',
            'course.courseDeadline',
        ])
            ->findOrFail($showDto->getAttendanceId());
    }
}
