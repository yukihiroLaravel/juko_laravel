<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(Instructor $user, Course $course): bool
    {
        // 講師が保有する講座なら処理可能
        if($user->type === Instructor::TYPE_INSTRUCTOR){
            return $user->id === $course->instructor_id;
        }

        // マネージャーでかつ、配下の講師の講座であれば処理可能
        if($user->type === Instructor::TYPE_MANAGER){
            return $user->managing->pluck('id')->contains($course->instructor_id);
        }

        // 上記以外は処理不可
        return false;

    }
}
