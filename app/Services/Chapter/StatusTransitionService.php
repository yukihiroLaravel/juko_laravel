<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use Illuminate\Validation\ValidationException;

class StatusTransitionService
{
    /**
     * ステータスの変更が許可されるかどうかを検証する
     *
     */
    public function __invoke(StatusEnum $current, StatusEnum $target): void
    {
        if ($current === $target) {
            return;
        }
    
        $allowed = match ($current) {
            StatusEnum::DRAFT => $target === StatusEnum::PUBLIC,
            StatusEnum::PUBLIC => $target === StatusEnum::PRIVATE,
            StatusEnum::PRIVATE => $target === StatusEnum::PUBLIC,
        };
    
        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => ['ステータスを「'.$current->value.'」から「'.$target->value.'」へ変更することはできません。'],
            ]);
        }
    }
}
