<?php

namespace App\Http\Controllers\Api\Manager;

use Carbon\Carbon;
use App\Model\Course;
use App\Model\Student;
use App\Model\Instructor;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Manager\StudentShowRequest;
use App\Http\Requests\Manager\StudentIndexRequest;
use App\Http\Requests\Manager\StudentStoreRequest;
use App\Http\Resources\Manager\StudentShowResource;
use App\Http\Resources\Manager\StudentIndexResource;
use App\Http\Resources\Manager\StudentStoreResource;

class StudentController extends Controller
{
    /**
     * 受講生一覧取得API
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
        $inputText = $request->input('input_text');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $instructorId = $request->user()->id;//Instracter側との違い確認

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
     * 受講生詳細取得API
     *
     * @param StudentShowRequest $request
     * @return StudentShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(StudentShowRequest $request)
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
        $student = Student::with(['attendances'])->findOrFail($request->student_id);

        // 受講生が講師の講座に所属しているか確認
        $studentCourseIds = $student->attendances->pluck('course_id')->unique();
        if ($studentCourseIds->intersect($courseIds)->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => 'Not authorized to access this student.'
            ], 403);
        }

        //受講生の年齢を算出
        $birthDay = $student->birth_date; /*メンバ変数birth_date*/
        $toDay = Carbon::today();
        $ageData = $birthDay->diffInYears($toDay);

        return new StudentShowResource([
            'student' => $student, 
            'ageData' => $ageData,
        ]);
    }

    /**
     * 受講生登録API
     *
     * @param StudentStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StudentStoreRequest $request)
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
