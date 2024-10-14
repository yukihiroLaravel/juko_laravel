<?php

namespace App\Services\Attendance;

use App\Model\Course;
use App\Model\Chapter;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 受講中の講座一覧を取得
     *
     * @param int $studentId
     * @param string $request
     * @return Collection<Attendance>
     */
    public function getAttendancesByStudentIdAndSearchWords(int $studentId, string $searchWord): Collection
    {
        return Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use($searchWord) {
            $query->where('status', Course::STATUS_PUBLIC)
                ->when($searchWord, function (Builder $query, $searchWord) {
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
    public function getAttendanceById(int $attendanceId): Attendance
    {
        return $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($attendanceId);
    }

    /**
     * チャプターIDと一致するチャプター詳細情報を取得
     *
     * @param $attendance
     * @param int $chapterId
     * @return Chapter
     */
    public function getChapterByChapterId($attendance, int $chapterId): Chapter
    {
        return $chapter = $attendance->course->chapters->filter(function ($chapter) use ($chapterId) {
            return $chapter->id === $chapterId;
    })
        ->first();
    }
}