<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use App\Http\Requests\Instructor\StudentIndexRequest;
use App\Http\Resources\Instructor\StudentIndexResource;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Resources\Instructor\StudentShowResource;
use App\Http\Requests\Instructor\StudentStoreRequest;
use App\Http\Resources\Instructor\StudentStoreResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * 講師側受講生一覧取得API
     *
     * @param StudentIndexRequest $request
     * @return StudentIndexResource
     */
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $attendances = Attendance::with(['student', 'course'])
                                    ->where('course_id', $request->course_id)
                                    ->paginate($perPage, ['*'], 'page', $page);

        $course = Course::find($request->course_id);

        return new StudentIndexResource([
            'course' => $course,
            'attendances' => $attendances,
        ]);
    }

    /**
     * 講座受講生詳細情報を取得
     *
     * @param StudentShowRequest $request
     * @return StudentShowResource
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
