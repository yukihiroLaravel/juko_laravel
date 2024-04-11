<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StudentLessonStatusController extends Controller
{
    /**
     * 受講生詳細学習状況取得API
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([]);
    }
}