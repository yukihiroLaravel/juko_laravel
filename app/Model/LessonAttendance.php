<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class LessonAttendance extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lesson_attendances';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'lesson_id',
        'attendance_id',
        'status'
    ];

    /**
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // ステータス定数
    const STATUS_BEFORE_ATTENDANCE = 'before_attendance';
    const STATUS_IN_ATTENDANCE = 'in_attendance';
    const STATUS_COMPLETED_ATTENDANCE = 'completed_attendance';

    // 期間内の受講状況を取得する際の期間に関する定数
    const PERIOD_TODAY = 'today';
    const PERIOD_MONTH = 'month';

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
