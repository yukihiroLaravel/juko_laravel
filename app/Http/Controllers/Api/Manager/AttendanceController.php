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
use App\Http\Requests\Manager\AttendanceStoreRequest;
use App\Http\Requests\Manager\AttendanceDeleteRequest;
use App\Http\Requests\Manager\AttendanceShowThisMonthRequest;

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
    public function showStatusThisMonth(AttendanceShowThisMonthRequest $request): JsonResponse
    {
        $attendances = Attendance::with('lessonAttendances.lesson.chapter.course')->where('course_id', $request->course_id)->get();

        //現在ログインしているinstructorのidを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        //ログインしているインストラクターとその管理しているインストラクターを取得
        $manager = Instructor::with('managings')->find($instructorId);
        //管理しているインストラクターのIDを配列として取得し、自分自身のIDも追加
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //自分と配下のinstructorのコースでなければエラー応答
        $course = Course::findOrFail($request->course_id);
        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // エラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to access this course.",
            ], 403);
        }

        // 今月完了したレッスンの個数を取得
        $completedLessonsCount = $attendances->flatMap(function (Attendance $attendance) {
            $compleatedLessonAttendances = $attendance->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) {
                return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE && $lessonAttendance->updated_at->isCurrentMonth();
            });
            return $compleatedLessonAttendances;
        })->count();

        // 今月完了したチャプターの個数を取得
        $completedChaptersCount = $attendances->flatMap(function (Attendance $attendance) {
            return $attendance->lessonAttendances->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE);
        })
        ->filter(function (LessonAttendance $lessonAttendance) {
            // チャプターに含まれているレッスンが全て完了されているかつ、最新のレッスンの完了済みステータスへの更新日時が今月の日時という条件で絞り込む
            $allLessonsId = $lessonAttendance->lesson->chapter->lessons->pluck('id');
            $totalLessonsCount = $allLessonsId->count();
            $compleatedLessonsCount = $lessonAttendance->where('attendance_id', $lessonAttendance->attendance_id)
                ->whereIn('lesson_id', $allLessonsId)
                ->where('status', LessonAttendance::STATUS_COMPLETED_ATTENDANCE)
                ->count();
            return $lessonAttendance->updated_at->isCurrentMonth() && $totalLessonsCount === $compleatedLessonsCount;
        })
        ->map(function (LessonAttendance $lessonAttendance) {
            // chapter_idとattendance_idをキーにもつ新しい配列を作成
            return [
                'chapter_id' => $lessonAttendance->lesson->chapter_id,
                'attendance_id' => $lessonAttendance->attendance_id
            ];
        })
        ->unique()
        ->count();

        return response()->json([
            'completed_lessons_count' => $completedLessonsCount,
            'completed_chapters_count' => $completedChaptersCount
        ]);
    }
}
