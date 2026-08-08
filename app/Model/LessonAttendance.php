<?php

namespace App\Model;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'attendance_id',
        'status',
        'completed_at',
    ];

    // ステータス定数
    const STATUS_BEFORE_ATTENDANCE = 'before_attendance';

    const STATUS_IN_ATTENDANCE = 'in_attendance';

    const STATUS_COMPLETED_ATTENDANCE = 'completed_attendance';

    // 期間内の受講状況を取得する際の期間に関する定数
    const PERIOD_TODAY = 'today';

    const PERIOD_MONTH = 'month';

    /**
     * 表示用ステータスを変更する
     *
     * 完了日時は過去に完了した事実を保つため、まだ記録がない場合にだけ現在時刻を記録する
     */
    public function changeStatus(string $status): void
    {
        $this->status = $status;

        if ($status === self::STATUS_COMPLETED_ATTENDANCE && $this->completed_at === null) {
            $this->completed_at = CarbonImmutable::now();
        }

        $this->save();
    }

    /**
     * レッスン取得
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * 受講
     *
     * @return BelongsTo<Attendance, $this>
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
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
