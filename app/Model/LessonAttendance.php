<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $lesson_id
 * @property int $attendance_id
 * @property 'before_attendance'|'in_attendance'|'completed_attendance' $status
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 * @property Lesson $lesson
 * @property Attendance $attendance
 */
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

    // ステータス定数
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

    /**
     * レッスンの完了した総数を取得する
     *
     * @param int $lessonid
     * @param int $attendanceid
     * @return int
     */
    public static function countCompletedAttendance($lessonId, $attendanceId)
    {
        return self::where('lesson_id', $lessonId)
                    ->where('attendance_id', $attendanceId)
                    ->where('status', self::STATUS_COMPLETED_ATTENDANCE)
                    ->count();
    }
}
