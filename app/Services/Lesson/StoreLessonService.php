<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;

class StoreLessonService
{
    public function __invoke(
        int $courseId,
        int $chapterId,
        string $title,
    ): Lesson {
        $nextOrder = Lesson::where('chapter_id', $chapterId)->max('order') + 1;

        $lesson = Lesson::create([
            'chapter_id' => $chapterId,
            'title' => $title,
            'status' => StatusEnum::DRAFT->value,
            'order' => $nextOrder,
        ]);
        return $lesson;
    }
}
