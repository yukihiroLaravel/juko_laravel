<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lessons';

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lessonAttendance()
    {
        return $this->hasMany(LessonAttendance::class);
    }
}
