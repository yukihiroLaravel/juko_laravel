<?php

namespace App\Services\Course;

use App\Model\Course;

class PutStatusService
{
    /**
     * 講座の状態を更新
     *
     * @param  array<int>  $instructorIds
     * @param  'public'|'private'  $status
     */
    public function __invoke(array $instructorIds, string $status): void
    {
        Course::whereIn('instructor_id', $instructorIds)->update(['status' => $status]);
    }
}
