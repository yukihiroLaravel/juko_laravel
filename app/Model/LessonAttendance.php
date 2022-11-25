<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lesson_attendances';

    /**
     * レッスン取得
     *
     * @return  \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
