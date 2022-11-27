<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    protected $table = 'lesson_attendances';

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attendance()
    {
        return $this->belongTo(Attendance::class);
    }
}
