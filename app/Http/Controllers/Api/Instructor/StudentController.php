<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Instructor\StudentIndexRequest;
use App\Http\Resources\Instructor\StudentIndexResource;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Resources\Instructor\StudentShowResource;
use App\Http\Requests\Instructor\StudentStoreRequest;
use App\Http\Resources\Instructor\StudentStoreResource;
use Carbon\Carbon;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Builder;
=======
use Illuminate\Support\Facades\Auth;
>>>>>>> a74ac012262a9093e4a2c1ae0516e3362e12e828

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
        $sortBy = $request->input('sortBy', 'nick_name');
        $order = $request->input('order', 'asc');
        $loginId = Auth::guard('instructor')->user()->id;
        $instructorId = Course::findOrFail($request->course_id)->instructor_id;

        if ($loginId !== $instructorId) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }

        $attendances = Attendance::with(['student', 'course'])
            ->where('course_id', $request->course_id)
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderBy($sortBy, $order)
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
        // 講師が作成した講座のIDを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        // 講座に紐づく受講生を取得
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendances'])
                            ->findOrFail($request->student_id);

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
