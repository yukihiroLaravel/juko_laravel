<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $course_id
 * @property int $instructor_id
 * @property string $title
 * @property 'always'|'once' $type
 * @property string $start_date
 * @property string $end_date
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 * @property Course $course
 * @property Collection<Student> $students
 */
class Notification extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'notifications';

    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'type',
        'start_date',
        'end_date',
        'content',
    ];

    // 表示区分 定数
    const TYPE_ALWAYS_INT = 1;
    const TYPE_ONCE_INT = 2;
    const TYPE_ALWAYS = 'always';
    const TYPE_ONCE = 'once';

    // ソート項目 定数
    const SORT_BY_TITLE = 'title';
    const SORT_BY_COURSE_ID = 'course_id';
    const SORT_BY_START_DATE = 'start_date';

    /**
     * 表示区分
     *
     * @return string|null
     */
    public function getTypeAttribute($value)
    {
        if ($value === self::TYPE_ALWAYS_INT) {
            return self::TYPE_ALWAYS;
        } elseif ($value === self::TYPE_ONCE_INT) {
            return self::TYPE_ONCE;
        }
        return null;
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = null;

        if ($value === self::TYPE_ALWAYS) {
            $this->attributes['type'] = self::TYPE_ALWAYS_INT;
        } elseif ($value === self::TYPE_ONCE) {
            $this->attributes['type'] = self::TYPE_ONCE_INT;
        }
    }

    /**
     * 受講生を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'viewed_once_notifications', 'notification_id', 'student_id')->withTimestamps();
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public static function typeUpdateAll(int $notifications,string $type): void
    {
        Notification::where('notificationId', $notifications)
            ->update([
                'type' => $type
            ]);
    }
}
