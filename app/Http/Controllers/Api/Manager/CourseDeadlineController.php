<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Course\ClearAllDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Manager\Course\ClearSelectedDeadlineRequest;

class CourseDeadlineController extends Controller
{
    /**
     * すべての講座の受講期限をクリアする
     */
    public function clearSelected(ClearSelectedDeadlineRequest $request, ClearAllDeadlineService $service): JsonResponse
    {
        $courseIds = $request->validated()['course_ids'];

        // managerIDを取得
        $managerId = Auth::guard('instructor')->id();

        DB::transaction(function () use ($service, $managerId, $courseIds) {
            $service($managerId, $courseIds);
        });

        return response()->json(['result' => true], 200);
    }
}
