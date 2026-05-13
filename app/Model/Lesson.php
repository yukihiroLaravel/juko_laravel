<?php

namespace App\Model;

use App\Enums\Lesson\StatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'lessons';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'chapter_id',
        'title',
        'url',
        'remarks',
        'status',
        'order',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
        ];
    }

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
        return $this->lessonAttendances->filter(fn (LessonAttendance $lessonAttendance) => $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE)->count();
    }

    /**
     * 公開済みのレッスンに絞り込む
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('status', StatusEnum::PUBLIC->value);
    }
}
