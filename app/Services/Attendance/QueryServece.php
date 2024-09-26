<?php

namespace App\Services\Attendance;

use App\Model\Course;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択されたチャプターを取得
     *
     * @param int $studentId
     * @return Attendance
     */
    public function getAttendances(int $studentId, $request)
    {
        return Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use ($request) {
            $query->where('title', 'like', "%{$request->search_word}%");
            $query->where('status', Course::STATUS_PUBLIC);
        })->get();
    }
}
