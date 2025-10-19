<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Course\ClearAllDeadlineService;
use App\Model\Course;

class CourseDeadlineController extends Controller
{
    public function clearAll(ClearAllDeadlineService $service): JsonResponse
    {
       // 現在ログイン中の講師IDを取得
        $instructorId = Auth::guard('instructor')->id();

        // 講師本人の講座IDを収集
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        // サービスを呼び出して処理実行
        DB::transaction(function () use ($service, $courseIds) {
            $service($courseIds);
        });

        return response()->json(['result' => true], 200);
    }
}
