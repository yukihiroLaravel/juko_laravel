<?php

namespace App\Dto\Student\Attendance;

class ShowDto
{
    public function __construct(private readonly int $attendanceId) {}

    public function getAttendanceId(): int
    {
        return $this->attendanceId;
    }
}
