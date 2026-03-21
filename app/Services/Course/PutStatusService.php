<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Support\Collection;

class PutStatusService
{
    /**
     * 選択した講座のステータスを更新する
     *
     * @param  Collection<int, Course>  $courses
     * @param  'public'|'private'  $status
     */
    public function __invoke(Collection $courses, string $status): void
    {
        // 講座のステータスを一括更新
        Course::whereIn('id', $courses->pluck('id'))->update(['status' => $status]);
    }
}
