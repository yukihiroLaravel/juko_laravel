<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Services\Instructor\CourseCapacity\ClearAllCapacityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseCapacityController extends Controller
{
    /**
     * 受講定員一括削除API
     */
    public function clearAll(Request $request, ClearAllCapacityService $service): JsonResponse
    {
        // 自分の講座を取得
        $courses = Course::where('instructor_id', $request->user('instructor')->id)->get();

        // 認可
        $this->authorize('bulkUpdate', [Course::class, $courses]);

        // Service実行
        $service($courses);

        return response()->json([
            'result' => true,
        ]);
    }
}
