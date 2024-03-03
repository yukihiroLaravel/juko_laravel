<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\AttendanceStoreRequest;
use App\Http\Requests\Manager\AttendanceDeleteRequest;

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

    // 配下のinstructorの講座に紐づく受講情報を削除
    /**
     * 受講状況削除API
     *
     * @param AttendanceDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(AttendanceDeleteRequest $request)
    {
        DB::beginTransaction();

        $instructorId = Auth::guard('instructor')->user()->id;
        $manager= Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        try {
            $attendanceId = $request->route('attendance_id');
            $attendance = Attendance::with('lessonAttendances')->findOrFail($attendanceId);

            if (Auth::guard('instructor')->user()->id !== $attendance->course->instructor_id) {
                return response()->json([
                    "result" => false,
                    "message" => "Unauthorized: The authenticated instructor does not have permission to delete this attendance record",
                ], 403);
            }

            $attendance->delete();

            DB::commit();

            return response() ->json([
                "result" => true,
            ]);

        }catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}