<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SortLessonsService
{
    /**
     * レッスン並び替えサービス order値は、配列要素のインデックス値にする。１から始まる値にする。
     */
    public function __invoke(Collection $lessons, array $inputLessons): void
    {
        $inputLessonCollection = collect($inputLessons);

        $lessons->each(function (Model $model) use ($inputLessonCollection) {
            if (! $model instanceof Lesson) {
                return;
            }

            $lesson = $model;

            // インデックスを取得
            $index = $inputLessonCollection->search(fn (array $input) => $input['lesson_id'] === $lesson->id);

            if ($index !== false) {
                $lesson->update([
                    'order' => $index + 1,
                ]);
            }
        });
    }
}
