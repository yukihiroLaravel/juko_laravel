<?php

namespace App\Http\Controllers\Api\Manager;
use App\Http\Resources\Manager\CourseIndexResource;
use App\Http\Controllers\Controller;

use App\Model\Course;
use App\Model\Instructor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


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
        $instructor = Instructor::with('managings')->find($instructorId);

        $managingIds = $instructor->managings->pluck('id')->toArray();
        $managingIds[] = $instructorId;

        // 自分と配下instructorのコース情報を取得
        $courses = Course::with('instructor')
                    ->whereIn('instructor_id', $managingIds)
                    ->get();

        return new CourseIndexResource($courses);
    }

}