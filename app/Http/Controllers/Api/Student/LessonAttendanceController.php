<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\LessonAttendance\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\Lesson\PatchStatusRequest;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * @tags Student-LessonAttendance
 */
class LessonAttendanceController extends Controller
{
    /**
     * レッスン出席状況更新API
     */
    public function patchStatus(PatchStatusRequest $request): JsonResponse
    {
        try {
            $lessonAttendance = LessonAttendance::with('attendance.course')
                ->find($request->lesson_attendance_id);
            assert($lessonAttendance instanceof LessonAttendance);

            $this->authorize('updateLessonAttendance', $lessonAttendance->attendance);

            $status = $request->enum('status', StatusEnum::class);
            assert($status instanceof StatusEnum);

            $lessonAttendance->changeStatus($status);

            return response()->json([
                'result' => true,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
