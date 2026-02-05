<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CourseDeadlineController extends Controller
{
    // 受講期限一括変更
    public function bulkUpdate(): JsonResponse
    {
        return response()->json([]);
    }

}
