<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Student\IndexRequest;
use App\Http\Resources\Manager\StudentIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @tags Manager-Student
 */
class StudentController extends Controller
{
    /**
     * 受講生一覧取得API
     *
     * @return StudentIndexResource|\Illuminate\Http\JsonResponse
     */
    public function index(IndexRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'nick_name');
        $order = $request->input('order', 'asc');
        $inputText = $request->input('input_text');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->findOrFail($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分、または配下の講師の講座IDのリストを取得
        $courseIds = Course::with('instructor')
            ->whereIn('instructor_id', $instructorIds)
            ->pluck('id')
            ->toArray();

        // クエリパラメータからcourses（配列）を取得
        $requestedCourseIds = $request->input('courses', []);
        // 指定された講座IDが有効かどうかチェック
        if (! empty($requestedCourseIds)) {
            foreach ($requestedCourseIds as $courseId) {
                if (! in_array((int) $courseId, $courseIds, true)) {
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
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
            // 複数の講座IDで絞り込み
            ->when(! empty($requestedCourseIds), fn (Builder $query) => $query->whereIn('attendances.course_id', $requestedCourseIds))
            // 受講生名検索（ニックネーム/メールアドレス/姓名）
            ->when($inputText, function (Builder $query) use ($inputText) {
                $inputText = preg_replace('/[　\s]/u', '', (string) $inputText);
                $query->where(function ($query) use ($inputText) {
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
}
