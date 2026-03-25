<?php

namespace App\Services\Instructor;

use App\Model\Instructor;

class InstructorCourseCapacityService
{
    /**
     * 講師のcapacity_totalを計算する
     */
    public function __invoke(Instructor $instructor): ?int
    {
        if ($instructor->courses()->whereNull('capacity')->exists()) {
            $instructor->capacity_total = null;
        }

        return (int) $instructor->courses()->sum('capacity');
    }
}