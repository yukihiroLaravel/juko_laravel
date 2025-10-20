<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Model\Instructor;
use App\Services\Course\ClearAllDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseDeadlineController extends Controller
{
    /**
     * すべての講座の受講期限をクリアする
     */
    public function clearAll(ClearAllDeadlineService $service): JsonResponse
    {
        // managerIDを取得
        $managerId = Auth::guard('instructor')->id();

        /** @var \App\Model\Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);

        // 配下講師 + 自身 の講師ID集合
        $instructorIds = $manager->managings->pluck('id')->push($manager->id);

        // その全講師に紐づく講座IDを収集
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

        // サービスを呼び出して処理実行
        DB::transaction(function () use ($service, $courseIds) {
            $service($courseIds);
        });

        return response()->json(['result' => true], 200);
    }
}
