<?php

namespace App\Model;

use App\Enums\Lesson\StatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * @var list<string>
     */
    protected $fillable = [
        'chapter_id',
        'title',
        'url',
        'remarks',
        'status',
        'order',
    ];

    /**
     * @return array{
     *  status: 'App\Enums\Lesson\StatusEnum'
     * }
     */
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
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * レッスン受講状態を取得
     *
     * @return HasMany<LessonAttendance, $this>
     */
    public function lessonAttendances()
    {
        return $this->hasMany(LessonAttendance::class);
    }

    /**
     * レッスンの総数を取得する
     */
    protected function totalLessonsCount(): Attribute
    {
        return Attribute::make(get: fn () => $this->chapter->lessons->count());
    }

    /**
     * レッスンの完了数を取得する
     */
    protected function completedLessonsCount(): Attribute
    {
        return Attribute::make(get: fn () => $this->lessonAttendances->filter(fn (LessonAttendance $lessonAttendance) => $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE)->count());
    }

    /**
     * 公開済みのレッスンに絞り込む
     */
    protected function scopePublic(Builder $query): void
    {
        $query->where('status', StatusEnum::PUBLIC->value);
    }
}
