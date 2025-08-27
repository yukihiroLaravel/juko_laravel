<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\CarbonImmutable;

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
    ];
    
    /** JSONに常に含める“計算項目” */
    protected $appends = ['deadline_date', 'expired'];

    public function getDeadlineDateAttribute(): ?string 
    {
        $d = $this->calcDeadlineForStudent();
        return $d ? $d->format('Y-m-d') : null;
    }
    
    public function getExpiredAttribute(): bool 
    {
        return $this->isExpired();
    }

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
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'student_id' => 'int',
            'course_id' => 'int',
            'progress' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    /**
     * 受講者ごとの最終期限（当日 23:59:59）。期限なしなら null。
     * - fixed_date があればそれを優先
     * - なければ relative_days を「受講開始日（created_at）」から加算
     */
    public function calcDeadlineForStudent(): ?CarbonImmutable
    {
    // N+1避けのため、呼び出し側では with('course.deadline') を推奨
    $course   = $this->course;
    $deadline = $course?->deadline; // Course::deadline() (hasOne)

    if (! $deadline) {
        return null; // 期限設定なし
    }

    // 1) 固定日が設定されている場合はそれを優先
    if (! empty($deadline->fixed_date)) {
        return CarbonImmutable::parse($deadline->fixed_date)->endOfDay();
    }

    // 2) 相対日（受講開始からN日）
    if (! empty($deadline->relative_days)) {
        $start = CarbonImmutable::parse($this->created_at);
        return $start->addDays((int)$deadline->relative_days)->endOfDay();
    }

    return null; // どちらも未設定＝期限なし
    }

    /** 期限切れか？ */
    public function isExpired(): bool
    {
        $limit = $this->calcDeadlineForStudent();
        return $limit ? CarbonImmutable::now()->greaterThan($limit) : false;
    }

}
