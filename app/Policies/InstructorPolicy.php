<?php

namespace App\Policies;

use App\Model\Instructor;

class InstructorPolicy
{
    public function view(Instructor $loginUser, Instructor $instructor): bool
    {
        if ($loginUser->isManager()) {
            $instructorIds = $loginUser->managings->pluck('id')->toArray();
            // 自分自身も許可対象に含める
            $instructorIds[] = $loginUser->id;

            return in_array($instructor->id, $instructorIds, true);
        }

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
