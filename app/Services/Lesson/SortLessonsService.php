<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Database\Eloquent\Collection;

class SortLessonsService
{
    /**
     * レッスン並び替えサービス order値は、配列要素のインデックスにする。１から始まる値にする。
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Model\Lesson>  $lessons
     */
    public function __invoke(Collection $lessons, array $inputLessons): void
    {
        $lessons->each(function (Lesson $lesson) use ($inputLessons) {
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
