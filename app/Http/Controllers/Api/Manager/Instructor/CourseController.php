<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\InstructorCourseIndexRequest;
use App\Http\Resources\Manager\InstructorCourseIndexResource;
use App\Model\Instructor;
use App\Services\Course\QueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * 講師-講座情報一覧取得API
     *
     * @return InstructorCourseIndexResource|JsonResponse
     */
    public function index(InstructorCourseIndexRequest $request, QueryService $queryService)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (! in_array((int) $request->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $courses = $queryService->getPaginatedCoursesByInstructorId($request->instructor_id, 5);

        // 指定した講師の講座一覧を取得
        $chapter = Chapter::with(['course', 'lessons'])->findOrFail($request->chapter_id);
        
        // 各講座に受講中の受講生がいるかを判定
        $courses->each(function ($course) {
            $lessonIds = $course->chapters->flatMap(function (Chapter $chapter) {
                return $chapter->lessons->pluck('id');
            });
            // 受講中の受講生がいるかを判定
            $hasActiveStudents = LessonAttendance::whereIn('lesson_id', $lessonIds)
                 ->where('status', LessonAttendance::STATUS_IN_ATTENDANCE)
                 ->exists();

            // フィールドを追加
            $course->has_active_students = $hasActiveStudents;
        });

        $lessonIds = Lesson::where('course_id', $courseId)->pluck('id')->toArray();

        return new InstructorCourseIndexResource($courses);
    }
}
