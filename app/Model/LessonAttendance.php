<?php

namespace App\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonAttendance extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lesson_attendances';

    protected $fillable = [
        'lesson_id',
        'attendance_id',
        'status'
    ];

    // ToDo ステータス定数
    const STATUS_BEFORE_ATTENDANCE = 'before_attendance';
    const STATUS_IN_ATTENDANCE = 'in_attendance';
    const STATUS_COMPLETED_ATTENDANCE = 'completed_attendance';

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
