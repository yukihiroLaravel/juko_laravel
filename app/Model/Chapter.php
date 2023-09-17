<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use SoftDeletes;
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'chapters';

    // ステータス定数
    const STATUS_PUBLIC = 'public';
    const STATUS_PRIVATE = 'private';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'chapter_id',
        'course_id',
        'order',
        'title',
        'status'
    ];

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
     * レッスンを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    protected static function boot() 
    {
        parent::boot();
        static::deleting(function($chapter) {
            foreach ($chapter->lessons()->get() as $child) {
                $child->delete();
            }
        });
    }

    /**
     * 公開中のチャプターを抽出
     *
     * @param \Illuminate\Support\Collection $chapters
     * @return \Illuminate\Support\Collection
     */
    public static function extractPublicChapter($chapters)
    {
        return $chapters->filter(function ($chapter) {
            return $chapter->status === Chapter::STATUS_PUBLIC;
        });
    }
}
