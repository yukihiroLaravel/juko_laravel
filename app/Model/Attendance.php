<?php

namespace App\Model;

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
}
