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
    public function getAttendancesByStudentIdAndSearchWord(int $studentId, string $searchWord): Collection
    {
        return Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use ($searchWord) {
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
     * 公開されているチャプターのみ抽出
     *
     * @param int $attendanceId
     * @return Attendance
     */
    public function getPublicChapterById(int $attendanceId): Attendance
    {
        $attendance = QueryService::getAttendanceById($attendanceId);
        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return $attendance;
    }
}
