<?php

namespace App\Http\Controllers\Api\Instructor;

use Carbon\Carbon;
use App\Model\Course;
use App\Model\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Instructor\StudentShowRequest;
use App\Http\Requests\Instructor\StudentIndexRequest;
use App\Http\Requests\Instructor\StudentStoreRequest;
use App\Http\Resources\Instructor\StudentShowResource;
use App\Http\Resources\Instructor\StudentIndexResource;
use App\Http\Resources\Instructor\StudentStoreResource;

class StudentController extends Controller
{
    /**
     * 受講生一覧取得API
     *
     * @param StudentIndexRequest $request
     * @return StudentIndexResource|JsonResponse
     */
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'nick_name');
        $order = $request->input('order', 'asc');
        $inputText = $request->input('input_text');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

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
                'attendances.student_id',
                'students.nick_name',
                'students.email',
                'students.profile_image',
                'students.last_login_at',
                'attendances.created_at as attendanced_at'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->where('attendances.course_id', $request->course_id)
            ->whereNull('attendances.deleted_at')
            // 受講生名検索（ニックネーム/メールアドレス/姓名）
            ->when($inputText, function ($query) use ($inputText) {
                $inputText = preg_replace('/[　\s]/u', '', $inputText);
                $query->where(function ($query) use ($inputText) {
                    $query->orWhere('students.nick_name', 'LIKE', "%{$inputText}%")
                        ->orWhere('students.email', 'LIKE', "%{$inputText}%")
                        ->orWhere(DB::raw("CONCAT(students.last_name, students.first_name)"), 'LIKE', "%{$inputText}%");
                });
            })
            // 日付検索
            ->when($startDate, function ($query) use ($startDate) {
                $query->where('attendances.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->where('attendances.created_at', '<=', $endDate);
            })
            // ソート
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        $course = Course::find($request->course_id);
        return new StudentIndexResource([
            'course' => $course,
            'data' => $results
        ]);
    }

    /**
     * 受講生を取得
     *
     * @param StudentShowRequest $request
     * @return StudentShowResource|JsonResponse
     */
    public function show(StudentShowRequest $request)
    {
        // 認証された講師のIDを取得
        $instructorCourseIds = Auth::guard('instructor')->user()->id;

        // 認証された講師が作成した講座のIDを取得
        $courseIds = Course::where('instructor_id', $instructorCourseIds)->pluck('id');

        // リクエストされた受講生を取得
        /** @var Student $student */
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

    /**
     * 受講生登録API
     *
     * @param StudentStoreRequest $request
     * @return JsonResponse
     */
    public function store(StudentStoreRequest $request): JsonResponse
    {
        /** @var Student $student */
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
