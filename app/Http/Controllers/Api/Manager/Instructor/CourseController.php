<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\InstructorCourseIndexRequest;
use App\Http\Resources\Manager\InstructorCourseIndexResource;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * 講師-講座情報一覧取得API
     *
     * @param InstructorCourseIndexRequest $request
     * @return InstructorCourseIndexResource|\Illuminate\Http\JsonResponse 
     */
    public function index(InstructorCourseIndexRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $managerId;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (!in_array((int)$request->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to this instructor.",
            ], 403);
        }

        /** @var Instructor $instructor */
        $courses = Course::where('instructor_id', $request->instructor_id)
            ->paginate(5);

        return new InstructorCourseIndexResource($courses);
    }
}
