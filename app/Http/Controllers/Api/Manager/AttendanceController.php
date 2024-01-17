<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\Instructor;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Http\Requests\Manager\AttendanceStoreRequest;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function store(AttendanceStoreRequest $request)
    {
        // 配下のinstructor情報を取得
        $instructorId = $request->user()->id;
        $manager = Instructor::with('managings')->findOrFail($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分と配下instructorのコース情報を取得
        $courseIds = Course::with('instructor')
            ->whereIn('instructor_id', $instructorIds)
            ->pluck('id')
            ->toArray();

        $course = Course::find($request->course_id);

        // 自分もしくは配下instructorのコースでない場合はエラーを返す
        if (!in_array($course->id, $courseIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }

        // コースと生徒を指定して受講情報を取得
        $attendance = Attendance::where('course_id', $request->course_id)
            ->where('student_id', $request->student_id)
            ->first();

        // 受講情報が存在すれば、エラーを返す
        if ($attendance) {
            return response()->json([
                'result' => false,
                'message' => 'Attendance record already exists.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // コース受講情報を登録
            $attendance = Attendance::create([
                'course_id'  => $request->course_id,
                'student_id' => $request->student_id,
                'progress'   => Attendance::PROGRESS_DEFAULT_VALUE
            ]);
            // 指定したコースのレッスン情報を取得
            $lessons = Lesson::whereHas('chapter', function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })->get();
            // レッスン受講情報を登録
            foreach ($lessons as $lesson) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id'     => $lesson->id,
                    'status'        => LessonAttendance::STATUS_BEFORE_ATTENDANCE
                ]);
            }
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
