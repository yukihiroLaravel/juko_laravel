<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Controllers\Controller;
use App\Model\Course;
use App\Model\CourseDeadline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Instructor-Course-Deadline
 */
class CourseDeadlineController extends Controller
{
    /**
     * 講座受講期限一括変更API（講師側）
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        // ログイン中の講師
        $instructor = Auth::guard('instructor')->user();

        // バリデーション
        $validated = $request->validate([
            'deadline_type' => ['required', 'string'],
            'fixed_date'    => ['nullable', 'date_format:Y-m-d'],
            'relative_days' => ['nullable', 'integer', 'min:0'],
        ]);

        // enum に変換
        $deadlineType = DeadlineTypeEnum::from($validated['deadline_type']);
        $fixedDate    = $validated['fixed_date']    ?? null;
        $relativeDays = $validated['relative_days'] ?? null;

        // 対象講座を取得（この講師の講座のみ）
        $courses = Course::where('instructor_id', $instructor->id)->get();

        if ($courses->isEmpty()) {
            return response()->json([
                'result'        => true,
                'updated_count' => 0,
            ]);
        }

        // Policy による認可チェック（CoursePolicy@update）
        foreach ($courses as $course) {
            $this->authorize('update', $course);
        }

        $courseIds     = $courses->pluck('id');
        $updatedCount  = 0;

        DB::transaction(function () use ($courseIds, $deadlineType, $fixedDate, $relativeDays, &$updatedCount) {
            // courses テーブル側の期限タイプを更新
            Course::whereIn('id', $courseIds)->update([
                'deadline_type' => $deadlineType->value,
            ]);

            // 期限設定が不要（NONE）の場合は course_deadlines を削除
            if (! $this->hasDeadline($deadlineType)) {
                CourseDeadline::whereIn('course_id', $courseIds)->delete();
                $updatedCount = $courseIds->count();

                return;
            }

            // 期限ありの場合の属性をまとめて作成
            $attributes = $this->buildDeadlineAttributes($deadlineType, $fixedDate, $relativeDays);

            // 各講座の受講期限を upsert
            foreach ($courseIds as $courseId) {
                CourseDeadline::updateOrCreate(
                    ['course_id' => $courseId],
                    $attributes
                );

                $updatedCount++;
            }
        });

        return response()->json([
            'result'        => true,
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * 受講期限設定があるかどうかを判定
     */
    private function hasDeadline(DeadlineTypeEnum $deadlineType): bool
    {
        return in_array($deadlineType, [
            DeadlineTypeEnum::FIXED_DATE,
            DeadlineTypeEnum::RELATIVE_DAYS,
        ], true);
    }

    /**
     * CourseDeadline に保存する属性をまとめる
     */
    private function buildDeadlineAttributes(
        DeadlineTypeEnum $deadlineType,
        ?string $fixedDate,
        ?int $relativeDays
    ): array {
        return [
            'fixed_date'    => $deadlineType === DeadlineTypeEnum::FIXED_DATE
                ? $fixedDate
                : null,
            'relative_days' => $deadlineType === DeadlineTypeEnum::RELATIVE_DAYS
                ? $relativeDays
                : null,
        ];
    }
}