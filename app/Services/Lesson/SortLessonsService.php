<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Support\Collection;

class SortLessonsService
{
    /**
     * @param Collection $lessons
     * @param array $inputLessons
     * @return void
     */
    public function __invoke(Collection $lessons, array $inputLessons): void
    {
        $inputLessonCollection = collect($inputLessons);

        $lessons->each(function (Lesson $lesson) use ($inputLessonCollection) {
            $input = $inputLessonCollection->firstWhere('lesson_id', $lesson->id);
            if ($input) {
                $lesson->update([
                    'order' => $input['order'],
                ]);
            }
        });
    }
}
