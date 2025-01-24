<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StudentIndexRequest;
use App\Http\Requests\Manager\StudentShowRequest;
use App\Http\Requests\Manager\StudentStoreRequest;
use App\Http\Resources\Manager\StudentIndexResource;
use App\Http\Resources\Manager\StudentShowResource;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use App\Services\Student\QueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * 受講生一覧取得API
     *
     * @return StudentIndexResource|\Illuminate\Http\JsonResponse
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

        $instructorId = $request->user()->id; //Instracter側との違い確認

        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分、または配下の講師の講座IDのリストを取得
        $courseIds = Course::with('instructor')
            ->whereIn('instructor_id', $instructorIds)
            ->pluck('id')
            ->toArray();

        // クエリパラメータからcourse_idを取得
        $courseId = (int)$request->query('course_id');

        \Log::info('Instructor ID:', ['instructor_id' => $instructorId]);
        \Log::info('Instructor IDs (including managings):', ['instructor_ids' => $instructorIds]);
        \Log::info('Course IDs:', ['course_ids' => $courseIds]);
        \Log::info('Requested Course ID:', ['course_id' => $courseId]);

        // クエリパラメータにcourse_idが存在する場合の処理
        if ($courseId) {
            if (! in_array($courseId, $courseIds, true)) {
                // 指定されたcourse_idが自分または配下の講師の講座に所属しているか確認
                return response()->json([
                    'result' => false,
                    'message' => 'Not authorized.',
                ], 403);
            }
        }

        $results = DB::table('attendances')
            ->select(
                'attendances.student_id',
                'students.nick_name',
                'students.email',
                'students.profile_image',
                'students.last_login_at',
                'attendances.id as attendance_id',
                'attendances.created_at as attendanced_at'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->when($courseId, function ($query) use ($courseId) {
                $query->where('attendances.course_id', $courseId);
            })
            // 受講生名検索（ニックネーム/メールアドレス/姓名）
            ->when($inputText, function ($query) use ($inputText) {
                $inputText = preg_replace('/[　\s]/u', '', $inputText);
                $query->where(function ($query) use ($inputText) {
                    $query->orWhere('students.nick_name', 'LIKE', "%{$inputText}%")
                        ->orWhere('students.email', 'LIKE', "%{$inputText}%")
                        ->orWhere(DB::raw('CONCAT(students.last_name, students.first_name)'), 'LIKE', "%{$inputText}%");
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

        return new StudentIndexResource([
            'data' => $results,
        ]);
    }

    /**
     * 受講生詳細取得API
     *
     * @return StudentShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(StudentShowRequest $request, QueryService $queryService)
    {
        // 認証されたマネージャーが管理する講師のIDのリストを取得
        $authManagerId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($authManagerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();

        // 自身のIDを追加
        $instructorIds[] = $authManagerId;

        // 認証されたマネージャーとマネージャーが管理する講師の講座IDのリストを取得
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

        // リクエストされた受講生を取得
        $student = $queryService->getStudent($request->student_id);

        // 受講生が講師の講座に所属しているか確認
        $studentCourseIds = $student->attendances->pluck('course_id')->unique();
        if ($studentCourseIds->intersect($courseIds)->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized to access this student.',
            ], 403);
        }

        return new StudentShowResource($student);
    }

    /**
     * 受講生登録API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StudentStoreRequest $request)
    {
        /** @var Student $student */
        $student = Student::create([
            'given_name_by_instructor' => $request->given_name_by_instructor,
            'email' => $request->email,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
