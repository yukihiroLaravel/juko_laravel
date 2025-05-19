<?php

namespace App\Services\Lesson;

use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

class DeleteAllLessonsService
{
    public function __invoke(Chapter $chapter): void
    {
        $lessonIds = $chapter->lessons->pluck('id');

        // 出席済みのレッスンがあれば削除不可
        $attendedLessonIds = LessonAttendance::whereIn('lesson_id', $lessonIds)->pluck('lesson_id');
        if ($attendedLessonIds->isNotEmpty()) {
            throw new AuthorizationException('This lessons contains attendance.');
        }

        DB::beginTransaction();

        try {
            // レッスン削除
            $chapter->lessons()->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
