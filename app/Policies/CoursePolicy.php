<?php

namespace App\Policies;

use App\Model\Course;
use App\Model\Instructor;

class CoursePolicy
{
    public function update(Instructor $instructor, Course $course): bool
    {
        // 講師の場合の処理
        if($instructor->id === $course->instructor_id){
            return true;
        }

        // 管理者の場合の処理
        if($instructor->isManager()){
            $manager = Instructor::with('managings')->find($instructor->id);

            if(!$manager){
                return false;
            }

            $managerIds = $manager->managings->pluck('id')->toArray();
            return in_array($course->instructor_id, $managerIds);
        }

        return false;
    }
}
