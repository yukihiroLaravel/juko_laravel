<?php

namespace App\Dto\Student\Attendance;

class ShowDto
{
    /**
     * @param  int  $userId
     */
    public function __construct(private readonly int $attendanceId, private $userId) {}

    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    public function getUserId()
    {
        return $this->userId;
    }
}
