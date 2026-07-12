<?php

namespace App\Services\Lesson;

use App\Model\Lesson;
use Illuminate\Support\Facades\DB; // 追記
 use App\Services\Lesson\StatusTransitionService;//追記
 use App\Services\Lesson\LessonAttendance\GenerateForPublishedLessonService;//追記

class UpdateLessonService
{
    /**
     * レッスン内容を更新する
     */
    public function __invoke(Lesson $lesson, string $title, string $url, ?string $remarks): void
    {
        $lesson->update([
            'title' => $title,
            'url' => $url,
            'remarks' => $remarks,
        ]);
        // ここでトランザクションを一括管理する
        DB::transaction(function () use ($lesson, $title, $url, $remarks, $status) {
            
            // 1. 状態遷移のバリデーション（例外が投げられればここで止まる）
            app(StatusTransitionService::class)->validateTransition($lesson->status, $status);

            // 更新前のステータスを保持（受講状況生成の判定用）
            $previousStatus = $lesson->status;

            // 2. レッスンの更新
            $lesson->update([
                'title'   => $title,
                'url'     => $url,
                'remarks' => $remarks,
                'status'  => $status,
            ]);

            // 3. draft → public 遷移時の受講状況生成
            if ($previousStatus === 'draft' && $status === 'public') {
                app(GenerateForPublishedLessonService::class)->execute($lesson->id);
            }
        });
    }
}