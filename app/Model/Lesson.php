<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    public function chaputer()
    {
        return $this->$belongsTo(chapter::class);
    }
    
    public function lessonattendance()
    {
        return $this->hasMany(LessonAttendance::class);
    }
}