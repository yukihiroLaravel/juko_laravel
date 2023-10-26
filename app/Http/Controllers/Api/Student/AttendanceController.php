<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Student\AttendanceIndexRequest;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Model\Course;
use Illuminate\Database\Eloquent\Builder;

class AttendanceController extends Controller
{
    /**
     * 受講中講座一覧取得API
     *
     * @param AttendanceIndexRequest $request
     * @return AttendanceIndexResource
     */
    public function index (AttendanceIndexRequest $request) {
        $studentId = Auth::id();
        $attendances = Attendance::with('course.instructor')->where('student_id', $studentId)->whereHas('course', function (Builder $query) {
            $query->where('status', Course::STATUS_PUBLIC);
        })->get();

        if (!$request->search_word) {
            return new AttendanceIndexResource($attendances);
        }   

        $attendances = Attendance::with('course.instructor')->where('student_id', $studentId)->whereHas('course', function (Builder $query) use($request) {
            $query->where('title', 'like', "%{$request->search_word}%");
            $query->where('status', Course::STATUS_PUBLIC);
        })->get();

        return new AttendanceIndexResource($attendances);
    }
}
