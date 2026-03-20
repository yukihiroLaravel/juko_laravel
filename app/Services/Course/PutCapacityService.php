<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PutCapacityService
{
    /**
     * 選択した講座定員を一括更新する
     *
     * @param  Collection<int, Course>  $courses
     * @param  int|null  $capacity
     */
    public function __invoke(Collection $courses, ?int $capacity): void
    {
        // 定員が現在の受講者数を下回っていないかチェック
        if ($capacity !== null) {
            foreach ($courses as $course) {
                if ($course->attendances_count > $capacity) {
                    throw ValidationException::withMessages([
                        'capacity' => '定員は現在の受講者数（'.$course->attendances_count.'人）以上に設定してください。',
                    ]);
                }
            }
        }

        // 講座定員を一括更新
        Course::whereIn('id', $courses->pluck('id'))->update(['capacity' => $capacity]);
    }
}
