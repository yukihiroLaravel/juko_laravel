<?php

namespace App\Http\Controllers\Api\Instructor;

use Carbon\Carbon;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Requests\Instructor\StudentIndexRequest;
use App\Http\Requests\Instructor\StudentStoreRequest;
use App\Http\Resources\Instructor\StudentShowResource;
use App\Http\Resources\Instructor\StudentIndexResource;
use App\Http\Resources\Instructor\StudentStoreResource;

class StudentController extends Controller
{
    /**
     * 講師側受講生一覧取得API
     *
     * @param StudentIndexRequest $request
     * @return StudentIndexResource|\Illuminate\Http\JsonResponse
     */
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'nick_name');
        $order = $request->input('order', 'asc');
        $loginId = Auth::guard('instructor')->user()->id;
        $instructorId = Course::findOrFail($request->course_id)->instructor_id;

        if ($loginId !== $instructorId) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }

        $results = DB::table('attendances')
            ->select(
                'attendances.*',
                'students.nick_name',
                'students.email',
                'students.profile_image',
                'students.last_login_at'
            )
            ->where('attendances.course_id', $request->course_id)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->when($sortBy === 'attendanced_at', function ($query) use ($order) {
                $query->orderBy('attendances.created_at', $order);
            }, function ($query) use ($sortBy, $order) {
                $query->orderBy('students.' . $sortBy, $order);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        $course = Course::find($request->course_id);
        return new StudentIndexResource([
            'course' => $course,
            'data' => $results
        ]);
    }

    /**
     * 講座受講生詳細情報を取得
     *
     * @param StudentShowRequest $request
     * @return StudentShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(StudentShowRequest $request)
    {
        // 認証された講師のIDを取得
        $instructorCourseIds = Auth::guard('instructor')->user()->id;

        // 認証された講師が作成した講座のIDを取得
        $courseIds = Course::where('instructor_id', $instructorCourseIds)->pluck('id');

        // リクエストされた受講生を取得
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendances'])
                            ->findOrFail($request->student_id);

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

    /**
     * 受講生登録API
     *
     * @param StudentStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StudentStoreRequest $request)
    {
        $student = Student::create([
            'given_name_by_instructor' => $request->given_name_by_instructor,
            'email' => $request->email,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return response()->json([
            'result' => true,
            'data' => new StudentStoreResource($student)
        ]);
    }
}
