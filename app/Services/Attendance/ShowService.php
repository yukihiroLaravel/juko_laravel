<?php

namespace App\Services\Attendance;

use App\Model\Attendance;
use Carbon\CarbonImmutable;

class ShowService
{
    public function __invoke(Attendance $attendance): array
    {
        /** @var int */
        $studentsCount = Attendance::where('course_id', $attendance->course_id)
            ->where(function ($query) {
                $query->whereNull('attendance_deadline')
                    ->orWhere('attendance_deadline', '>=', CarbonImmutable::today()->toDateString());
            })
            ->count();

        return [
            'attendance' => $attendance,
            'studentsCount' => $studentsCount,
        ];
    }
}
