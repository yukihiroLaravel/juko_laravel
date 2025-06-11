<?php

namespace App\Services\Course;

use App\Model\Course;

class StatusService
{
    /**
     * 講座のstatusを更新
     */
    public function __invoke(array $instructorIds, string $status): void
    {
        Course::whereIn('instructor_id', $instructorIds)->update(['status' => $status]);
    }
}
