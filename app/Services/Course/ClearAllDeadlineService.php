<?php

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;

class ClearAllDeadlineService
{
    /**
     * @param  iterable<int,int>  $courseIds  講座IDの集合
     */
    public function __invoke(iterable $courseIds): void
    {
        $ids = collect($courseIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        Course::whereIn('id', $ids)->update([
            'deadline_type' => DeadlineTypeEnum::NONE,
        ]);

        CourseDeadline::whereIn('course_id', $ids)->delete();
    }
}
