<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Http\Requests\Manager\StudentIndexRequest;
use App\Http\Resources\Manager\StudentIndexResource;

class StudentController extends Controller
{
    /**
     * マネージャ講座の受講生取得API
     *
     * @param StudentIndexRequest $request
     * @return StudentIndexResource|\Illuminate\Http\JsonResponse
     */
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'nick_name');
        $order = $request->input('order', 'asc');
        $instructorId = $request->user()->id;

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
}
