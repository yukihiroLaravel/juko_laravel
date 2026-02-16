<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Course\ClearSelectedDeadlineRequest;
use App\Services\Course\ClearSelectedDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseDeadlineController extends Controller
{
    /**
     * すべての講座の受講期限をクリアする
     */
    public function clearSelected(ClearSelectedDeadlineRequest $request, ClearSelectedDeadlineService $service): JsonResponse
    {
        $courseIds = $request->validated()['courses'];

        // managerIDを取得
        $managerId = Auth::guard('instructor')->id();

        DB::transaction(function () use ($service, $managerId, $courseIds) {
            $service($managerId, $courseIds);
        });

        return response()->json(['result' => true], 200);
    }
}
