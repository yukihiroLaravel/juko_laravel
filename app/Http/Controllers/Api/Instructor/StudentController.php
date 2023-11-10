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
use App\Http\Requests\Instructor\SortStudentsRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
        $ascOrDesc = $request->input('in:asc,desc', 'asc');
        $loginId = Auth::guard('instructor')->user()->id;
        $instructorId = Course::findOrFail($request->course_id)->instructor_id;

        if ($loginId !== (int)$instructorId) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized.'
            ], 403);
        }

        $attendances = Attendance::with(['student', 'course'])
            ->where('course_id', $request->course_id)
            ->when($this->isStudentColumn($sortBy), function (Builder $query) {
                $query->join('students', 'attendances.student_id', '=', 'students.id');
            })
            ->when($this->isCourseColumn($sortBy), function (Builder $query) {
                $query->join('courses', 'attendances.course_id', '=', 'courses.id');
            })
            ->orderBy($sortBy, $ascOrDesc)
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
        // TODO 講師が作成した講座に紐づく受講生のみ取得
        $student = Student::with(['attendances.course.chapters.lessons.lessonAttendances'])->findOrFail($request->student_id);
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

    /**
     * indexメソッドで受け取るカラム名がstudentsテーブル内の4つに該当するかをチェック
     *
     * @param string $sortBy
     * @return boolean
     */
    private function isStudentColumn($sortBy) {
        return in_array(
            $sortBy, 
            [
                Attendance::COLUMN_NICK_NAME, 
                Attendance::COLUMN_EMAIL, 
                Attendance::COLUMN_CREATED_AT, 
                Attendance::COLUMN_LAST_LOGIN_AT
            ], 
            true
        );
    }

    /**
     * indexメソッドで受け取るカラム名が講座テーブル内のtitleに一致するかをチェック
     *
     * @param string $sortBy
     * @return boolean
     */
    private function isCourseColumn($sortBy) {
        return in_array(
            $sortBy, 
            [
                Attendance::COLUMN_TITLE,
            ], 
            true
        );
    }
}
