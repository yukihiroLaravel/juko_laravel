<?php

namespace App\Services\Instructor\CourseCapacity;

use App\Model\Course;
use Illuminate\Support\Facades\Auth;

class ClearAllCapacityService
{
    /**
     * 講師の講座の定員をすべて削除
     */
    public function __invoke(): void
    {
        Course::where('instructor_id', Auth::id())->update(['capacity' => null]);
    }
}
