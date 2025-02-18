<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\LessonAttendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexService
{
    /**
     * @return Collection<Attendance>
     */
    public function __invoke(IndexDto $indexDto)
    {
        // 受講情報を関連情報と一緒に取得
        $attendances = Attendance::with([
            'course.instructor',
            'course.chapters.lessons',
            'lessonAttendances',
        ])
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $query->when(! $indexDto->getSearchWord(), function ($query) {
                    $query->where('status', Course::STATUS_PUBLIC);
                })->when($indexDto->getSearchWord(), function ($query) use ($indexDto) {
                    $query->where('title', 'like', "%{$indexDto->getSearchWord()}%")
                        ->where('status', Course::STATUS_PUBLIC);
                });
            })->get();

        // 各受講情報ごとにチャプター単位の進捗率を計算
        $attendances->each(function ($attendance) {
            $completedChaptersCount = $this->getCompletedChaptersCount($attendance);
            $totalChaptersCount = $this->getTotalChaptersCount($attendance);
            $progressPercentage = ($totalChaptersCount > 0) ? round(($completedChaptersCount / $totalChaptersCount) * 100) : 0;
            $attendance->course->progress_percentage = $progressPercentage;
        });

        return $attendances;
    }

    /**
     * 完了済みのチャプター数を取得する
     *
     * @param  Attendance  $attendance
     * @return int
     */
    private function getCompletedChaptersCount($attendance)
    {
        return $attendance->course->chapters->filter(function ($chapter) use ($attendance) {
            $isCompleted = false;
            // 全てのレッスンが完了済みかどうかをチェック
            $chapter->lessons->each(function ($lesson) use ($attendance, &$isCompleted) {
                $lessonAttendance = $attendance->lessonAttendances->where('lesson_id', $lesson->id)->first();
                if ($lessonAttendance->status !== LessonAttendance::STATUS_COMPLETED_ATTENDANCE) {
                    $isCompleted = false;

                    return false;
                }
                $isCompleted = true;
            });

            return $isCompleted;
        })->count();
    }

    /**
     * チャプター合計を取得する
     *
     * @param  Attendance  $attendance
     * @return int
     */
    private function getTotalChaptersCount($attendance)
    {
        return $attendance->course->chapters->count();
    }
}
