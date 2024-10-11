<?php

namespace App\Services\Attendance;

use App\Model\Course;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 受講中の講座一覧を取得
     *
     * @param int $studentId
     * @param $request
     * @return Collection<Attendance>
     */
    public function getAttendances(int $studentId, $request): Collection
    {
        return Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use($request) {
            $query->where('status', Course::STATUS_PUBLIC)
                ->when($request->search_word, function (Builder $query, $searchWord) {
                    return $query->where('title', 'like', "%{$searchWord}%");
                });
        })->get();
    }

     /**
     * 受講中の講座の詳細情報を取得
     *
     * @param int $attendanceId
     * @return Attendance
     */
    public function getAttendanceInDetails(int $attendanceId): Attendance
    {
        return $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($attendanceId);
    }

    /**
     * チャプター詳細情報を取得
     *
     * @param int $attendanceId
     * @return Attendance
     */
    public function getChapterInDetails(int $attendanceId): Attendance
    {
        return $attendance = Attendance::with([
            'course.chapters.lessons',
            'lessonAttendances'
        ])
        ->where('id', $attendanceId)
        ->firstOrFail();
    }
}