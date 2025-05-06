<?php

namespace App\Services\Lesson;

use Illuminate\Support\Collection;
use App\Model\Lesson;

class SortLessonsService
{
    /**
     * Create a new class instance.
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
