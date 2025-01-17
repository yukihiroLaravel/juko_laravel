<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LessonAttendancePatchRequest;
use App\Model\LessonAttendance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LessonAttendanceController extends Controller
{
    /**
     * レッスン出席状況更新API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function patchStatus(LessonAttendancePatchRequest $request)
    {
        try {
            $lessonAttendance = LessonAttendance::with('attendance')
                ->find($request->lesson_attendance_id);
            assert($lessonAttendance instanceof LessonAttendance);

            if ($request->user()->id !== $lessonAttendance->attendance->student_id) {
                throw new AuthorizationException('Forbidden, invalid student');
            }

            $lessonAttendance->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
