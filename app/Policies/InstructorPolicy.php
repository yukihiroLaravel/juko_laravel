<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Auth\Access\Response;

class InstructorPolicy
{
    /**
     * マネージャーは配下の講師と自分自身を更新可能。
     * 講師は自分自身のみ更新可能。
     *
     * @param  \App\Model\Instructor  $instructor  ログイン中、更新対象の講師     
     * @return bool
     */
    public function update(Instructor $instructor)
    {
        if ($instructor->isManager()) {
            $instructorIds = $instructor->managings->pluck('id')->toArray();
            $instructorIds[] = $instructor->id;
            return in_array($instructor->id, $instructorIds, true);
        }

        return $instructor->id === $instructor->id;
    }
    this->authorize('update');
}
