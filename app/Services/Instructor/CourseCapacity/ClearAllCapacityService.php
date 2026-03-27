<?php

namespace App\Services\Instructor\CourseCapacity;

use App\Model\Course;
use Illuminate\Support\Collection;

class ClearAllCapacityService
{
    /**
     * 講座の定員をすべて削除
     */
    public function __invoke(Collection $courses): void
    {
        Course::whereIn('id', $courses->pluck('id'))->update(['capacity' => null]);
    }
}
