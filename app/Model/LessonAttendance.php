<?php

namespace App\Model;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
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
     * @var array<int, string>
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
     * レッスン取得
     *
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * 受講
     *
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * レッスンが完了済みかどうか
     *
     * ステータスは表示用のため、完了判定は完了日時の有無で行う。
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * 受講状況を更新する
     *
     * 完了へ遷移した場合は完了日時を記録する。完了日時は最初に完了した時刻を
     * 保持し続けるため、記録済みの場合は上書きしない。
     */
    public function changeStatus(string $status): void
    {
        $attributes = ['status' => $status];

        if ($status === self::STATUS_COMPLETED_ATTENDANCE && $this->completed_at === null) {
            $attributes['completed_at'] = CarbonImmutable::now();
        }

        $this->update($attributes);
    }

    /**
     * 絞り込んだレッスン受講状況をまとめて完了にする
     *
     * 完了日時は最初に完了した時刻を保持し続けるため、未記録のものだけ現在日時を記録する。
     *
     * @param  Builder<LessonAttendance>  $query  更新対象を絞り込んだクエリ
     */
    public static function completeAll(Builder $query): void
    {
        (clone $query)
            ->whereNull('completed_at')
            ->update(['completed_at' => CarbonImmutable::now()]);

        $query->update(['status' => self::STATUS_COMPLETED_ATTENDANCE]);
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
