<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use App\Http\Requests\Manager\StudentIndexRequest;
use App\Http\Resources\Manager\StudentIndexResource;
use App\Http\Resources\Manager\StudentShowResource;
use App\Http\Requests\Manager\StudentShowRequest;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * マネージャ講座の受講生取得API
     *
     * @param StudentIndexRequest $request
     * @return StudentIndexResource
     */
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'nick_name');
        $order = $request->input('order', 'asc');
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
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

        // 指定した講座の受講生情報と講座情報を取得
        $attendances = Attendance::with(['course', 'student'])
            ->where('course_id', $request->course_id)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new StudentIndexResource([
            'course' => $course,
            'attendances' => $attendances,
        ]);
    }

    public function show(StudentShowRequest $request)
    {
        // 認証された講師のIDを取得
        $instructorCourseIds = Auth::guard('instructor')->user()->id;

        // 認証された講師が作成した講座のIDを取得
        $courseIds = Course::where('instructor_id', $instructorCourseIds)->pluck('id');

        // リクエストされた受講生を取得
        $student = Student::with(['attendances.course'])->findOrFail($request->student_id);

        // 受講生が講師の講座に所属しているか確認
        $studentCourseIds = $student->attendances->pluck('course_id')->unique();
        if ($studentCourseIds->intersect($courseIds)->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized to access this student.'
            ], 403);
        }
        return new StudentShowResource($student);
    }
}
