<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'attendances';

    /**
     * 受講生を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
        
    }

    public function lessonAttendaces()
    {
        return $this->hasOne(LessonaAttendance::class);
    }
}
