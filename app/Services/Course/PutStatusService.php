<?php

namespace App\Services\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class PutStatusService
{
    /**
     * 選択した講座のステータスを更新する
     *
     * @param  Collection<int, Course>  $courses
     * @param  'public'|'private'  $status
     */
    public function __invoke(Collection $courses, string $status): void
    {

        // 許容する組み合わせを検証する
        $this->validateStatusCombination($courses, $status);

        // 講座のステータスを一括更新
        Course::whereIn('id', $courses->pluck('id'))->update(['status' => $status]);
    }

    /**
     * 許容する組み合わせを検証する
     *
     * @param  Collection<int, Course>  $courses
     * @param  'public'|'private'  $status
     */
    private function validateStatusCombination(Collection $courses, string $status): void
    {
        // 対象のステータスをEnumに変換
        $target = StatusEnum::from($status);

        foreach ($courses as $course) {
            $current = $course->status;

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