<?php

namespace App\Services\Course;

use App\Model\Course;

class PutStatusService
{
    /**
     * 選択した講座のステータスを更新する
     *
     * @param  array<int>  $courseIds
     * @param  'public'|'private'  $status
     */
    public function __invoke(array $courseIds, string $status): void
    {
        // 講座のステータスを一括更新
        Course::whereIn('id', $courseIds)->update(['status' => $status]);
    }
}
