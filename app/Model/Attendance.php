<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_id
 * @property int $student_id
 * @property int $progress
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property Student $student
 * @property Course $course
 * @property Collection<LessonAttendance> $lessonAttendances
 */
class Attendance extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'attendances';

    protected $fillable = [
        'course_id',
        'student_id',
        'progress'
    ];

    protected $casts = [
        'student_id' => 'int',
        'course_id' => 'int',
        'progress' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * チャプターを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chapters()
    {
        return $this->course->chapters(Chapter::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($attendance) {
            $attendance->lessonAttendances()->delete();
        });
    }

    /**
 * 講師側受講生詳細画面の学習状況を取得
 *
 * @param int $attendanceId
 * @return array
 */
    public static function getInstructorAttendanceStatus(int $attendanceId): array
    {

        $attendance = self::find($attendanceId);


        if (!$attendance) {
            return [];
        }


        return [
        'attendance_id' => $attendance->id,
        'course' => [
            'course_id' => $attendance->course_id,
            'title' => $attendance->course->title,
            'progress' => $attendance->progress,
            'chapter' => $attendance->course->chapters()->select('id as chapter_id', 'title', 'progress')->get()->toArray(),
        ],
        ];
    }

    //ソート項目
    const SORT_BY_NICK_NAME = 'nick_name';
    const SORT_BY_EMAIL = 'email';
    const SORT_BY_ATTENDANCED_AT = 'attendanced_at';
    const SORT_BY_LAST_LOGIN_AT = 'last_login_at';

    //$periodのバリデーションに利用する定数
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';
}
