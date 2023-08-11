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
use Illuminate\Http\Request;
use App\Model\Student;
use Carbon\Carbon;

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
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendances'])->findOrFail($request->student_id);

        return new StudentShowResource($student);
     * 受講生登録API
     *
     * @param Request $request
     * @return Resource
     */
    }

    public function store(Request $request)
    {
        $student = null;
        if ( Student::where('email', $request->email)->first() === null ) {
            $student = Student::create([
                // 'given_name_by_instructor' => $request->name, まだ実装されてないのでnick_nameで代用
                'nick_name' => $request->nick_name,
                'email' => $request->email,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } else {
            return response()->json([
                'result' => false,
                "error_message" => "Invalid email.",
                "error_code" => "400"
            ]);
        }
        
        return response()->json([
            'result' => true,
            'data' => [
                'id' => $student->id,
                // $student->given_name_by_instructor, まだ実装されてないのでnick_nameで代用
                'nick_name' => $student->nick_name,
                'email' => $student->email
            ]
        ]);
    }
}