<?php

namespace App\Model;

use App\Enums\Chapter\StatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'chapters';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'chapter_id',
        'course_id',
        'order',
        'title',
        'status',
    ];

    /**
     * モデルのブート時の処理
     *
     * @return void
     */
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        // チャプター削除時に紐づくレッスンも削除
        static::deleting(function (Chapter $chapter) {
            $chapter->lessons()->delete();
        });
    }

    /**
     * @return array{
     *  status: 'App\Enums\Chapter\StatusEnum'
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
     * 講座を取得
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * レッスンを取得
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order', 'asc');
    }

    /**
     * 公開中のチャプターを抽出
     *
     * @param  Collection  $chapters
     * @return Collection
     */
    public static function extractPublicChapter($chapters)
    {
        return $chapters->filter(fn ($chapter) => $chapter->status === StatusEnum::PUBLIC);
    }

    /**
     * 公開中のチャプターに絞り込む
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('status', StatusEnum::PUBLIC->value);
    }

    /**
     * 公開中のレッスンを取得
     *
     * @return HasMany<Lesson, $this>
     */
    public function publicLessons(): HasMany
    {
        return $this->lessons()->public();
    }
}
