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

        $allowedTransitions = [
            StatusEnum::DRAFT->value => [
                StatusEnum::PUBLIC,
            ],
            StatusEnum::PUBLIC->value => [
                StatusEnum::PRIVATE,
            ],
            StatusEnum::PRIVATE->value => [
                StatusEnum::PUBLIC,
            ],
        ];

        if (! in_array($newStatus, $allowedTransitions[$currentStatus->value] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ['The selected status transition is invalid.'],
            ]);
        }
    }
}
