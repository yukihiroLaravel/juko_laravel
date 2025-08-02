<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Student\IndexRequest;
use App\Http\Requests\Instructor\Student\ShowRequest;
use App\Http\Requests\Instructor\Student\StoreRequest;
use App\Http\Resources\Instructor\StudentIndexResource;
use App\Http\Resources\Instructor\StudentShowResource;
use App\Model\Course;
use App\Model\Student;
use App\Services\Student\StoreStudentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Instructor-Student
 */
class StudentController extends Controller
{
    /**
     * 受講生一覧取得API
     */
    public function index(IndexRequest $request): StudentIndexResource
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'nick_name');
        $order = $request->input('order', 'asc');
        $inputText = $request->input('input_text');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $courseIds = $request->input('courses', []);

        $loginId = Auth::guard('instructor')->user()->id;

        if (! empty($courseIds)) {
            $courses = Course::whereIn('id', $courseIds)->get(['id', 'instructor_id']);

            foreach ($courses as $course) {
                if ($loginId !== $course->instructor_id) {
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            }
        }

        $results = DB::table('attendances')
            ->select(
                'attendances.student_id',
                'attendances.course_id',
                'courses.instructor_id',
                'students.nick_name',
                'students.email',
                'students.profile_image',
                'students.last_login_at',
                'attendances.id as attendance_id',
                'attendances.created_at as attendanced_at'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('courses', 'attendances.course_id', '=', 'courses.id')
            ->when(! empty($courseIds), function (Builder $query) use ($courseIds) {
                $query->whereIn('attendances.course_id', $courseIds);
            })
            // ログインしている講師IDを検索
            ->where('courses.instructor_id', $loginId)
            ->whereNull('attendances.deleted_at')
            // 受講生名検索（ニックネーム/メールアドレス/姓名）
            ->when($inputText, function (Builder $query) use ($inputText) {
                $inputText = preg_replace('/[　\s]/u', '', (string) $inputText);
                $query->where(function (Builder $query) use ($inputText) {
                    $query->orWhere('students.nick_name', 'LIKE', "%{$inputText}%")
                        ->orWhere('students.email', 'LIKE', "%{$inputText}%")
                        ->orWhere(DB::raw('CONCAT(students.last_name, students.first_name)'), 'LIKE', "%{$inputText}%");
                });
            })
            // 日付検索
            ->when($startDate, function (Builder $query) use ($startDate) {
                $query->where('attendances.created_at', '>=', $startDate);
            })
            ->when($endDate, function (Builder $query) use ($endDate) {
                $query->where('attendances.created_at', '<=', $endDate);
            })
            // ソート
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new StudentIndexResource($results);
    }

    /**
     * 受講生を取得
     *
     * @return StudentShowResource|JsonResponse
     */
    public function show(ShowRequest $request)
    {
        $student = Student::with('attendances')->findOrFail($request->student_id);

        // 認可チェック
        $this->authorize('view', $student);

        return new StudentShowResource($student);
    }


    /**
     * 受講生登録API
     */
    public function store(StoreRequest $request, StoreStudentService $service): JsonResponse
    {
        ($service)($request->only([
            'given_name_by_instructor',
            'email',
        ]));

        return response()->json([
            'result' => true,
        ]);
    }
}
