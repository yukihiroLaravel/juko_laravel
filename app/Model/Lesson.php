<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $chapter_id
 * @property string $title
 * @property string $url
 * @property string $remarks
 * @property 'public'|'private' $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $order
 * @property-read int $total_lessons_count
 * @property Chapter $chapter
 * @property Collection<LessonAttendance> $lessonAttendances
 */
class Lesson extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lessons';

    // ステータス定数
    const STATUS_PUBLIC = 'public';
    const STATUS_PRIVATE = 'private';

    protected $fillable = [
        'chapter_id',
        'title',
        'url',
        'remarks',
        'status',
        'order',
    ];

    /**
     * チャプターを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * レッスン受講状態を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessonAttendances()
    {
        return $this->hasMany(LessonAttendance::class);
    }

    /**
     * レッスンの総数を取得する
     *
     * @return int
     */
    public function getTotalLessonsCountAttribute()
    {
        return $this->chapter->lessons->count();
    }

    /**
     * レッスンの完了数を取得する
     *
     * @return int
     */
    public function getCompletedLessonsCountAttribute()
    {
        return $this->lessonAttendances->filter(function (LessonAttendance $lessonAttendance) {
            return $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })->count();
    }
}
