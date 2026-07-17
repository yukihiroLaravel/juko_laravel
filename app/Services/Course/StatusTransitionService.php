<?php

namespace App\Services\Course;

use App\Enums\Course\StatusEnum;
use Illuminate\Validation\ValidationException;

class StatusTransitionService
{
    /**
     * 講座ステータスの状態遷移を検証
     */
    public function __invoke(StatusEnum $currentStatus, StatusEnum $newStatus): void
    {
        // 1. 同一ステータスは即時終了
        if ($currentStatus === $newStatus) {
            return;
        }

        // 2. 許可リストを「文字列のみ」で定義
        $allowed = match ($currentStatus) {
            StatusEnum::DRAFT => [StatusEnum::PUBLIC->value],
            StatusEnum::PUBLIC => [StatusEnum::PRIVATE->value],
            StatusEnum::PRIVATE => [StatusEnum::PUBLIC->value],
            default => [],
        };

        // 3. 許可リストになければ「固定の文字列」でエラーを投げる
        if (! in_array($newStatus->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['このステータス遷移は許可されていません。'],
            ]);
        }
    }
}
