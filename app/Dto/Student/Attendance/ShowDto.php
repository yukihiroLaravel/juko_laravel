<?php

namespace App\Dto\Student\Attendance;

class ShowDto
{
    /** @var int */
    private $attendanceId;

    /** @var int */
    private $userId;

    public function __construct(int $attendanceId, $userId)
    {
        $this->attendanceId = $attendanceId;
        $this->userId = $userId;
    }

    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    public function getUserId()
    {
        return $this->userId;
    }
}