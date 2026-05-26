<?php

namespace App\Services\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class StatusTransitionService
{
    /**
     * ステータスの変更が許可されるかどうかを検証する
     *
     * @param  Collection<int, Chapter>  $chapters
     */
    public function __invoke(Collection $chapters, StatusEnum $target): void
    {

        foreach ($chapters as $chapter) {
            $current = $chapter->status;

            // 許容する組み合わせの検証
            $allowed = ($current === StatusEnum::DRAFT && $target === StatusEnum::PUBLIC)
                || ($current === StatusEnum::PUBLIC && $target === StatusEnum::PRIVATE)
                || ($current === StatusEnum::PRIVATE && $target === StatusEnum::PUBLIC)
                || ($current === StatusEnum::PUBLIC && $target === StatusEnum::PUBLIC)
                || ($current === StatusEnum::PRIVATE && $target === StatusEnum::PRIVATE);

            if (! $allowed) {
                throw ValidationException::withMessages([
                    'status' => $current->value.'は、'.$target->value.'に変更できません。',
                ]);
            }
        }
    }
}