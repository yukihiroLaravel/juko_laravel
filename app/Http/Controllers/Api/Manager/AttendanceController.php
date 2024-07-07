<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Manager\AttendanceStoreRequest;
use App\Http\Requests\Manager\AttendanceDeleteRequest;
use App\Http\Requests\Instructor\AttendanceShowRequest;
use App\Http\Requests\Instructor\AttendanceShowThisMonthRequest;

class AttendanceController extends Controller
{
    /**
     * 受講状況登録API
     *
     * @param AttendanceStoreRequest $request
     * @return JsonResponse
     */
    public function store(AttendanceStoreRequest $request): JsonResponse
    {
        $managerId = $request->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $courseIds = Course::with('instructor')
            ->whereIn('instructor_id', $instructorIds)
            ->pluck('id')
            ->toArray();

        /** @var Course $course */
        $course = Course::find($request->course_id);

        if (!in_array($course->id, $courseIds, true)) {
            // 自分もしくは配下の講師の講座でない場合はエラーを返す
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }

        if (Attendance::where('course_id', $request->course_id)->where('student_id', $request->student_id)->exists()) {
            // 受講状況が存在すれば、エラーを返す
            return response()->json([
                'result' => false,
                'message' => 'Attendance record already exists.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // 受講状況を登録
            /** @var Attendance $attendance */
            $attendance = Attendance::create([
                'course_id'  => $request->course_id,
                'student_id' => $request->student_id,
                'progress'   => Attendance::PROGRESS_DEFAULT_VALUE
            ]);

            // 指定した講座のレッスンを取得
            $lessons = Lesson::whereHas('chapter', function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })->get();

            // レッスン受講情報を登録
            $lessons->each(function (Lesson $lesson) use ($attendance) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id'     => $lesson->id,
                    'status'        => LessonAttendance::STATUS_BEFORE_ATTENDANCE
                ]);
            });

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

    /**
     * 受講状況削除API
     *
     * @param AttendanceDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(AttendanceDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        $instructorId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        try {
            $attendanceId = $request->attendance_id;

            // ログインしている講師、またはそのマネージャーが管理する受講データのIDのリストを取得
            $managedAttendances = Attendance::whereIn('course_id', $instructorIds)->pluck('id')->toArray();

            if (!in_array((int) $attendanceId, $managedAttendances, true)) {
                // ログインしている講師、またはそのマネージャーが管理する受講データでない場合はエラーを返す
                return response()->json([
                    "result" => false,
                    "message" => "Unauthorized: The authenticated instructor does not have permission to delete this attendance record",
                ], 403);
            }

            // 受講状況を削除
            Attendance::findOrFail($attendanceId)->delete();

            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
    /**
    * 今月のレッスン・チャプター完了数の取得API
    *
    *
    */
    
    /**
     * 講座受講状況-今月
     *
     * @param AttendanceShowThisMonthRequest $request
     * @return JsonResponse
     */
   public function showStatusThisMonth(Request $request): JsonResponse
   {
        $attendances = Attendance::with('lessonAttendances.lesson.chapter.course')->where('course_id', $request->course_id)->get();
    
        // 今月完了したレッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(function (Attendance $attendance) {
            $compleatedLessonAttendances = $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) {
                return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $lessonAttendance->updated_at->isCurrentMonth();
        });
        return $compleatedLessonAttendances;
    })->count();
       return response()->json([
        'completed_lessons_count' => $completedLessonsCount,
       ]);
   }
}
