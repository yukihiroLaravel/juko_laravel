<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    public function lesson()
    {
        return $this->hasMany(Lesson::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
