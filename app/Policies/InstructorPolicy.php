<?php

namespace App\Policies;

use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Auth\Access\Response;

class InstructorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Student $student): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Student $student, Instructor $instructor): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Student $student): bool
    {
        //
    }

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

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Student $student, Instructor $instructor): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Student $student, Instructor $instructor): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Student $student, Instructor $instructor): bool
    {
        //
    }
}
