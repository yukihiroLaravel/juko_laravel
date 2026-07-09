<?php

namespace App\Services\Lesson;

use App\Enums\Lesson\StatusEnum;
use Illuminate\Validation\ValidationException;

class StatusTransitionService
{
    /**
     * レッスンの状態遷移が許可されているか検証する
     *
     * @param  StatusEnum  $currentStatus  現在のステータス
     * @param  StatusEnum  $newStatus  変更後のステータス
     *
     * @throws ValidationException
     */
    public function validateTransition(StatusEnum $currentStatus, StatusEnum $newStatus): void
    {
        // 1. 同一ステータス間の更新はいつでも許可（実質変更なし）
        if ($currentStatus === $newStatus) {
            return;
        }

        // 2. 禁止遷移のチェック（どれか1つでも当てはまったらエラー）

        // 禁止①：下書き(draft)への後退は禁止
        $isBackToDraft = $newStatus === StatusEnum::DRAFT;

        // 禁止②：下書き(draft)から直接非公開(private)にするのは禁止（初回は必ずpublicのみ）
        $isDraftToPrivate = ($currentStatus === StatusEnum::DRAFT && $newStatus === StatusEnum::PRIVATE);

        if ($isBackToDraft || $isDraftToPrivate) {
            // Laravel標準のバリデーションエラー（422）と同じ形式のエラーを投げる
            throw ValidationException::withMessages([
                'status' => ['Invalid status transition.'],
            ]);
        }
    }
}
