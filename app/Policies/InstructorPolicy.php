<?php

namespace App\Policies;

use App\Model\Instructor;

class InstructorPolicy
{
    /**
     * マネージャーは配下の講師と自分自身を更新可能。
     * 講師は自分自身のみ更新可能。
     *
     * @param  \App\Model\Instructor  $loginUser  ログイン中の講師
     * @param  \App\Model\Instructor  $instructor  更新対象の講師
     * @return bool
     */
    public function update(Instructor $loginUser, Instructor $instructor)
    {
        if ($loginUser->isManager()) {
            $instructorIds = $loginUser->managings->pluck('id')->toArray();
            $instructorIds[] = $loginUser->id;

            return in_array($instructor->id, $instructorIds, true);
        }

        return $loginUser->id === $instructor->id;
    }
}
