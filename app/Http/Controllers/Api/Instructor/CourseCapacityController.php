<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CourseCapacityController extends Controller
{
    /**
     * 受講定員一括削除API
     */
    public function clearAll(): JsonResponse
    {
        return response()->json(['data' => []]);
    }
}
