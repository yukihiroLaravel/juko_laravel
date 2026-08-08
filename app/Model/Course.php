<?php

namespace App\Model;

use App\Enums\Course\StatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $has_active_students
 * @property int $progress_percentage
 * @property int|null $capacity
 * @property int|null $current_attendance_count
 */
class Course extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'courses';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'instructor_id',
        'title',
        'image',
        'status',
        'deadline_type',
        'capacity',
    ];

    /**
     * モデルのブート処理
     *
     * @return void
     */
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Course $course) {
            $course->created_at = CarbonImmutable::now();
            $course->updated_at = CarbonImmutable::now();
        });

        static::updating(function (Course $course) {
            $course->updated_at = CarbonImmutable::now();
        });

        // 削除時に関連するチャプターを削除
        static::deleting(function ($course) {
            foreach ($course->chapters()->get() as $child) {
                $child->delete();
            }

            // 講座期限設定があれば削除
            if ($course->courseDeadline()->exists()) {
                $course->courseDeadline()->delete();
            }
        });
    }

    /**
     * 講師を取得
     *
     * @return BelongsTo<Instructor, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * 受講状態を取得
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * チャプターリストを取得
     *
     * @return HasMany<Chapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order', 'asc');
    }

    /**
     * 公開済みのチャプターリストを取得
     *
     * @return HasMany<Chapter, $this>
     */
    public function publicChapters(): HasMany
    {
        return $this->chapters()->public();
    }

    /**
     * 画像保存パスに変換
     *
     * @return string
     */
    public static function convertImagePath(string $filePath)
    {
        // public/を削除
        return str_replace('public/', '', $filePath);
    }

    /**
     * タグを取得
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'course_tag', 'course_id', 'tag_id');
    }

    /**
     * 期限設定（1:1）
     *
     * @return HasOne<CourseDeadline, $this>
     */
    public function courseDeadline(): HasOne
    {
        return $this->hasOne(CourseDeadline::class);
    }

    /**
     * @return array{
     *  instructor_id: 'int',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime',
     *  capacity: 'integer',
     *  status: 'App\Enums\Course\StatusEnum'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'instructor_id' => 'int',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'capacity' => 'integer',
            'status' => StatusEnum::class,
        ];
    }

    /**
     * 定員に空きがあるか
     */
    public function hasCapacity(): bool
    {
        // 定員無制限の場合
        if ($this->capacity === null) {
            return true;
        }

        return $this->attendances()->count() < $this->capacity;
    }
}
