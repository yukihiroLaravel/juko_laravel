<?php

namespace App\Http\Resources\Student;

use App\Dto\Student\Attendance\ContinueFromDto;
use App\Dto\Student\Attendance\CourseProgressDto;
use App\Model\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCourseProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    #[\Override]
    public function toArray($request)
    {
        /** @var Attendance $attendance */
        $attendance = $this->resource['attendance'];
        /** @var CourseProgressDto $courseProgress */
        $courseProgress = $this->resource['courseProgress'];
        /** @var ContinueFromDto|null $continueFrom */
        $continueFrom = $this->resource['continueFrom'];

        return [
            'attendance' => [
                'attendance_id' => $attendance->id,
                'course' => [
                    'course_id' => $attendance->course->id,
                    'title' => $attendance->course->title,
                    'image' => $attendance->course->image,
                ],
            ],
            'number_of_completed_chapters' => $courseProgress->getCompletedChaptersCount(),
            'number_of_total_chapters' => $courseProgress->getTotalChaptersCount(),
            'number_of_completed_lessons' => $courseProgress->getCompletedLessonsCount(),
            'number_of_total_lessons' => $courseProgress->getTotalLessonsCount(),
            'continue_from' => $continueFrom?->toArray(),
        ];
    }
}
