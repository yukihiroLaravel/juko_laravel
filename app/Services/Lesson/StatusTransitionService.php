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
        // 1. 同一ステータスでない場合のみチェックする
        if ($currentStatus !== $newStatus) {

            // 2. 許可された遷移リスト
            $allowedTransitions = [
                StatusEnum::DRAFT->value => [StatusEnum::PUBLIC->value],
                StatusEnum::PUBLIC->value => [StatusEnum::PRIVATE->value],
                StatusEnum::PRIVATE->value => [StatusEnum::PUBLIC->value],
            ];

            // 3. 許可リストにない場合、強制的にエラーにする
            if (! isset($allowedTransitions[$currentStatus->value]) || ! in_array($newStatus->value, $allowedTransitions[$currentStatus->value])) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid status transition.'],
                ]);
            }
        }
    }
}
