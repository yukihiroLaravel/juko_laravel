<?php

namespace App\Services\Instructor;

use App\Model\Attendance;

class TotalCurrentAttendanceCountService
{
    public function __invoke(int $instructorId): int
    {
        return Attendance::whereNull('completed_at')
            ->whereHas('course', function ($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            })
            ->count();
    }
}
