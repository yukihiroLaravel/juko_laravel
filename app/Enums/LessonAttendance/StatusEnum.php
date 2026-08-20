<?php

namespace App\Enums\LessonAttendance;

enum StatusEnum: string
{
    case BEFORE_ATTENDANCE = 'before_attendance';
    case IN_ATTENDANCE = 'in_attendance';
    case COMPLETED_ATTENDANCE = 'completed_attendance';
}
