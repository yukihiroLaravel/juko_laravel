<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Services\Instructor\CourseCapacity\ClearAllCapacityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourseCapacityController extends Controller
{
    /**
     * 受講定員一括削除（manager）
     */
    public function clearAll(ClearAllCapacityService $service): JsonResponse
    {
        // 講座取得
        $courses = Course::where('instructor_id', Auth::id())->get();

        // 認可
        $this->authorize('bulkUpdate', [Course::class, $courses]);

        // 実行
        $service($courses);

        return response()->json([
            'result' => true,
        ]);
    }
}