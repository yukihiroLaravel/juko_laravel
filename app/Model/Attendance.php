<?php

namespace App\Model;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'attendances';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'student_id',
        'progress',
        'attendance_deadline',
        'completed_at',
    ];

    // 受講状態初期値
    const PROGRESS_DEFAULT_VALUE = 0;

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

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessonAttendances()
    {
        return $this->hasMany(LessonAttendance::class);
    }

    #[\Override]
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($attendance) {
            $attendance->lessonAttendances()->delete();
        });
    }

    /**
     * 受講生ログイン率計算
     */
    public static function calcLoginRate(int $number, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        $percent = ($number / $total) * 100;

        return floor($percent);
    }

    /**
     * 平均進捗率計算
     */
    public static function calcAverageProgressRate(int $completedLessonsCount, int $studentsCount, int $totalLessonsCount): float
    {
        if ($studentsCount === 0 || $totalLessonsCount === 0) {
            return 0;
        }

        $percent = ($completedLessonsCount / ($studentsCount * $totalLessonsCount)) * 100;

        return floor($percent);
    }

    /**
     * 全公開レッスンを完了しているか判定する
     *
     * @param  int  $totalPublicLessonsCount  公開レッスン総数
     */
    public function isAllPublicLessonsCompleted(int $totalPublicLessonsCount): bool
    {
        if ($totalPublicLessonsCount === 0) {
            return false;
        }

        $completedCount = $this->lessonAttendances
            ->filter(fn (LessonAttendance $lessonAttendance) =>
                $lessonAttendance->lesson->status === Lesson::STATUS_PUBLIC
                && $lessonAttendance->completed_at !== null
            )
            ->count();

        return $completedCount === $totalPublicLessonsCount;
    }

    /**
     * 修了率計算
     *
     * 全公開レッスンを完了している受講生の割合（%）を算出する。
     *
     * @param  int  $completedStudentsCount  全公開レッスンを完了している受講生数
     * @param  int  $studentsCount  全受講生数
     * @return float 修了率（%）。受講生が0人の場合は0を返す。
     */
    public static function calcCompletionRate(int $completedStudentsCount, int $studentsCount): float
    {
        if ($studentsCount === 0) {
            return 0;
        }

        $percent = ($completedStudentsCount / $studentsCount) * 100;

        return floor($percent);
    }

    public static function hasActiveStudents(int $courseId): bool
    {
        return self::where('course_id', $courseId)->exists();
    }

    // ソート項目
    const SORT_BY_NICK_NAME = 'nick_name';

    const SORT_BY_EMAIL = 'email';

    const SORT_BY_ATTENDANCED_AT = 'attendanced_at';

    const SORT_BY_LAST_LOGIN_AT = 'last_login_at';

    // $periodのバリデーションに利用する定数
    const PERIOD_WEEK = 'week';

    const PERIOD_MONTH = 'month';

    const PERIOD_YEAR = 'year';

    /**
     * @return array{
     *   student_id: 'int',
     *   course_id: 'int',
     *   progress: 'int',
     *   attendance_deadline: 'immutable_date',
     *   created_at: 'immutable_datetime',
     *   updated_at: 'immutable_datetime',
     *   completed_at: 'immutable_datetime',
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'student_id' => 'int',
            'course_id' => 'int',
            'progress' => 'int',
            'attendance_deadline' => 'immutable_date',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * 受講者ごとの最終期限（当日 23:59:59）。期限なしなら null。
     */
    public function getAttendanceDeadlineEndAttribute(): ?CarbonImmutable
    {

        return $this->attendance_deadline
            ? $this->attendance_deadline->endOfDay()
            : null;
    }

    /**
     * 受講期限切れかどうか
     */
    public function isExpired(): bool
    {
        return $this->attendance_deadline !== null && CarbonImmutable::now()->gte($this->attendance_deadline_end);
    }

    /**
     * 受講期限まで何日かを返す。期限なし・期限切れなら null。
     */
    public function getDaysUntilDeadline(): ?int
    {
        if ($this->attendance_deadline === null || $this->isExpired()) {
            return null;
        }

        return max(0, (int) CarbonImmutable::today()->diffInDays($this->attendance_deadline, false));
    }
}
