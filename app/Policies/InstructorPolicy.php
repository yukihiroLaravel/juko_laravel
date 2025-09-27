<?php

namespace App\Policies;

use App\Model\Instructor;

class InstructorPolicy
{
    public function view(Instructor $loginUser, Instructor $instructor): bool
    {
        // マネージャーの場合、管理する講師と自分自身のみ許可
        if ($loginUser->isManager()) {
            $instructorIds = $loginUser->managings->pluck('id')->toArray();
            // 自分自身も許可対象に含める
            $instructorIds[] = $loginUser->id;
            // 管理する講師の中に対象の講師がいるかどうか
            return in_array($instructor->id, $instructorIds, true);
        }
        // 一般講師の場合、自分自身のみ許可
        return $loginUser->id === $instructor->id;
    }

    public function update(Instructor $loginUser, Instructor $instructor): bool
    {
        if ($loginUser->isManager()) {
            $instructorIds = $loginUser->managings->pluck('id')->toArray();
            // 自分自身も許可対象に含める
            $instructorIds[] = $loginUser->id;

            return in_array($instructor->id, $instructorIds, true);
        }

        return $loginUser->id === $instructor->id;
    }
}
