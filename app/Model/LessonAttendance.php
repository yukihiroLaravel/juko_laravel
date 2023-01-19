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

    // ToDo ステータス定数
    const STATUS_NOT_ATTENDANCE = 'not_attendance';
    const STATUS_IN_ATTENDANCE = 'in_attendance';
    const STATUS_COMPLETED_COURSE = 'completed_courde';

    /**
     * レッスン取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * 受講
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
