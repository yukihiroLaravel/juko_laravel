<?php

namespace App\Services\Course;

use App\Model\Course;

class PutCapacityService
{
    /**
     * 選択した講座定員を一括更新する
     *
     * @param  array<int>  $courseIds
     * @param  int| null  $capacity
     */
    public function __invoke(array $courseIds, ?int $capacity): void
    {
        // 講座のステータスを一括更新
        Course::whereIn('id', $courseIds)->update(['capacity' => $capacity]);
    }
}
