<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Requests\Instructor\CoursePutStatusRequest;
use App\Http\Controllers\Controller;

use App\Model\Course;
use App\Model\Instructor;

use Illuminate\Http\Request;


class CourseController extends Controller
{
    /**
     * 講師側マネージャ講座一覧取得API
     *
     * @return CourseIndexResource
     */
    public function index(Request $request)
    {
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $manager = Instructor::with('managings')->find($instructorId);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // 自分と配下instructorのコース情報を取得
        $courses = Course::with('instructor')
                    ->whereIn('instructor_id', $instructorIds)
                    ->get();

        return new CourseIndexResource($courses);
    }

    /**
     * 講師側マネージャ講座ステータス一覧更新API
     *
     * @return JsonResponse
     */
    public function status(CoursePutStatusRequest $request)
    {
        $instructorId = $request->user()->id;

        // 配下のinstructor情報を取得
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 自分と配下instructorのコースのステータスを一括更新
        Course::whereIn('instructor_id', $managingIds)->update(['status' => $request->status]);
        return response()->json([
            'result' => 'true'
        ]);
    }

}