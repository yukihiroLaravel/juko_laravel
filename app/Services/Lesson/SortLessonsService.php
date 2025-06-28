<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SortLessonsService
{
    /**
     * レッスン並び替えサービス order値は、配列要素のインデックスにする。１から始まる値にする。
     */
    public function __invoke(Collection $lessons, array $inputLessons): void
    {
        $lessons->each(function (Model $model) use ($inputLessons) {
            if (! $model instanceof Lesson) {
                return;
            }

            $lesson = $model;

            // lessons配列内における要素のインデックスを取得
            $index = array_search($lesson->id, $inputLessons, true);

            if ($index !== false) {
                $lesson->update([
                    'order' => $index + 1,
                ]);
            }
        });
    }
}
