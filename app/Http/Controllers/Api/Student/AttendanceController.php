<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Resources\Student\AttendanceIndexResource;

class AttendanceController extends Controller
{
    /**
     * 受講中講座一覧取得API
     *
     * @param void
     * @return AttendanceIndexResource
     */
    public function index () {
        $student = Auth::id();
        $attendances = Attendance::with('course.instructor')->where('student_id', $student)->get();
        return response()->json([
            'data' => new AttendanceIndexResource($attendances)
        ]);
    }
}
