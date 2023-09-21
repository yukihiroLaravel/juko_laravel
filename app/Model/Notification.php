<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // 表示区分定数
    const TYPE_ALWAYS_INT = 1;
    const TYPE_ONCE_INT = 2;
    const TYPE_ALWAYS = 'always';
    const TYPE_ONCE = 'once';

    /**
     * 表示区分
     *
     * @return string
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
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'viewed_once_notifications', 'notification_id', 'student_id')->withTimestamps();
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
