<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Course\ClearAllDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseDeadlineController extends Controller
{
    /**
     * 全て受講期限をなくすAPI（仮）
     *
     * @return JsonResponse
     */
    public function clearAll(ClearAllDeadlineService $service): JsonResponse
    {
        // managerIDを取得
        $managerId = Auth::guard('instructor')->id();

        DB::transaction(function () use ($service, $managerId) {
            $service($managerId);
        });

        return response()->json(['result' => true], 200);
    }
}
