<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Course\ClearAllDeadlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use Illuminate\Http\Request;

class CourseDeadlineController extends Controller
{
    /**
     * 受講期限一括変更API（マネージャー側）
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        // 実行しているマネージャー
        $manager = Auth::guard('instructor')->user();

        // 簡易バリデーション
        $validated = $request->validate([
            'deadline_type' => ['required', 'string'],
            'fixed_date'    => ['nullable', 'date_format:Y-m-d'],
            'relative_days' => ['nullable', 'integer', 'min:0'],
        ]);

        // enum に変換
        $deadlineType = DeadlineTypeEnum::from($validated['deadline_type']);
        $fixedDate    = $validated['fixed_date']    ?? null;
        $relativeDays = $validated['relative_days'] ?? null;

        // マネージャー＋配下講師IDの一覧を取得
        /** @var Instructor $managerWithRelations */
        $managerWithRelations = Instructor::with('managings')->findOrFail($manager->id);
        $instructorIds = $managerWithRelations->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 対象講座ID一覧
        $courseIds = Course::whereIn('instructor_id', $instructorIds)->pluck('id');

        if ($courseIds->isEmpty()) {
            return response()->json([
                'result'        => true,
                'updated_count' => 0,
            ]);
        }

        $updatedCount = 0;

        DB::transaction(function () use ($courseIds, $deadlineType, $fixedDate, $relativeDays, &$updatedCount) {
            // courses テーブル側の期限タイプを更新
            Course::whereIn('id', $courseIds)->update([
                'deadline_type' => $deadlineType, 
            ]);

            if ($deadlineType === DeadlineTypeEnum::NONE) {
            CourseDeadline::whereIn('course_id', $courseIds)->delete();
            $updatedCount = $courseIds->count();
            return;
            }

            // course_deadlines を upsert
            foreach ($courseIds as $courseId) {
                /** @var CourseDeadline $deadline */
                $deadline = CourseDeadline::firstOrNew(['course_id' => $courseId]);

                switch ($deadlineType) {
                    case DeadlineTypeEnum::NONE:
                        $deadline->fixed_date    = null;
                        $deadline->relative_days = null;
                        break;

                    case DeadlineTypeEnum::FIXED_DATE:
                        $deadline->fixed_date    = $fixedDate;
                        $deadline->relative_days = null;
                        break;

                    case DeadlineTypeEnum::RELATIVE:
                        $deadline->fixed_date    = null;
                        $deadline->relative_days = $relativeDays;
                        break;
                }

                $deadline->save();
                $updatedCount++;
            }
        });

        return response()->json([
            'result'        => true,
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * すべての講座の受講期限をクリアする
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
