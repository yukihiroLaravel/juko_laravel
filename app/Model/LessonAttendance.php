<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonAttendance extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lesson_attendances';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'lesson_id',
        'attendance_id',
        'status',
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

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
