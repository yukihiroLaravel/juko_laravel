<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Course\ClearAllDeadlineService;
use Illuminate\Http\JsonResponse;

class CourseDeadlineController extends Controller
{
    /**
     * 全て受講期限をなくすAPI（仮）
     *
     * @return JsonResponse
     */
    public function clearAll(ClearAllDeadlineService $service): JsonResponse
    {
        // ルートは manager ミドルウェア配下なので認可は満たしている想定
        $service();

        return response()->json(['result' => true], 200);
    }
}
