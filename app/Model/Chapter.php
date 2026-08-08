<?php

namespace App\Model;

use App\Enums\Chapter\StatusEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * @var list<string>
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
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * レッスンを取得
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order', 'asc');
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

    /**
     * 公開中のチャプターに絞り込む
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->where('status', StatusEnum::PUBLIC->value);
    }
}
