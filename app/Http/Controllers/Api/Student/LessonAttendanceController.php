<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LessonAttendancePatchRequest;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Auth\Access\AuthorizationException;

class LessonAttendanceController extends Controller
{
    /**
     * レッスン出席状況更新API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LessonAttendancePatchRequest $request)
    {
        try {
            /** @var LessonAttendance $lessonAttendance */
            $lessonAttendance = LessonAttendance::with('attendance')
                ->find($request->lesson_attendance_id);

            if ($request->user()->id !== $lessonAttendance->attendance->student_id) {
                // ログインしている生徒が受講しているコースではない
                throw new AuthorizationException('Forbidden.');
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
