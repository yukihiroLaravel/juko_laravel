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
        if ($currentStatus === $newStatus) {
            return;
        }

        // 許可遷移: draft → public、public ⇔ private
        $allowed = match ($currentStatus) {
            StatusEnum::DRAFT => $newStatus === StatusEnum::PUBLIC,
            StatusEnum::PUBLIC => $newStatus === StatusEnum::PRIVATE,
            StatusEnum::PRIVATE => $newStatus === StatusEnum::PUBLIC,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => ['ステータスを「' . $currentStatus->value . '」から「' . $newStatus->value . '」へ変更することはできません。'],
            ]);
        }
    }
}
