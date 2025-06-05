<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Support\Collection;

class BulkUpdateLessonStatusService
{
    /**
     * レッスンのステータスを一括更新する
     * 
     * @param Collection<int, Lesson> $lessons
     * @param 'public'|'private' $status
     */
    public function __invoke(Collection $lessons, string $status): void
    {
        Lesson::whereIn('id', $lessons->pluck('id'))->update(['status' => $status]);
    }
}
