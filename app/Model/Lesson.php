<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function lessonAttendance()
    {
        return $this->hasMany(LessonAttendance::class);
    }
}
