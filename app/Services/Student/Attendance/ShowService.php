<?php

namespace App\Services\Student\Attendance;

use App\Model\Chapter;
use App\Model\Attendance;
use App\Dto\Student\Attendance\ShowDto;
use Illuminate\Auth\Access\AuthorizationException;

class ShowService
{
    /**
     * @param ShowDto $showDto
     * @return Attendance
     */
    public function __invoke(
        ShowDto $showDto
    ): Attendance {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($showDto->getAttendanceId());

        if ($attendance->student_id !== $showDto->getUserId()) {
            throw new AuthorizationException(
                "The attendance's student ID {$attendance->student_id} " . 
                "does not match User ID {$showDto->getUserId()}.");
        }

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;

        return $attendance;
    }
}