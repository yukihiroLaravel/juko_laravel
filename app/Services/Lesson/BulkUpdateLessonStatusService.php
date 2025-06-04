<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Support\Collection;

class BulkUpdateLessonStatusService
{
    /**
     * レッスンのステータスを一括更新する
     */
    public function __invoke(Collection $lessons, string $status): void
    {
        Lesson::whereIn('id', $lessons->pluck('id'))->update(['status' => $status]);
    }
}
