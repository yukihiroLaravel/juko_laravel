<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Database\Eloquent\Collection;

class SortLessonsService
{
    /**
     * レッスン並び替えサービス order値は、配列要素のインデックス値にする。１から始まる値にする。
     */
    public function __invoke(Collection $lessons, array $inputLessons): void
    {
        $inputLessonCollection = collect($inputLessons);

        $lessons->each(function (Lesson $lesson) use ($inputLessonCollection) {
            // インデックスを取得
            $index = $inputLessonCollection->search(function ($input) use ($lesson) {
                return $input['lesson_id'] === $lesson->id;
            });

            if ($index !== false) {
                $lesson->update([
                    'order' => $index + 1,
                ]);
            }
        });
    }
}
