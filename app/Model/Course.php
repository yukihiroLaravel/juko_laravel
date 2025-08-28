<?php

namespace App\Model;

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

    // ステータス定数
    const STATUS_PUBLIC = 'public';

    const STATUS_PRIVATE = 'private';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'instructor_id',
        'title',
        'image',
        'status',
        'deadline_type',
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
        return $this->chapters()->where('status', Chapter::STATUS_PUBLIC);
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
     * @return array{
     *  instructor_id: 'int',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'instructor_id' => 'int',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * 受講期限を当日 23:59:59 に揃えて返す
     */
    public function getAttendanceDeadlineEndAttribute(): ?CarbonImmutable
    {
        return $this->attendance_deadline
            ? $this->attendance_deadline->endOfDay()
            : null;
    }

    /**
     * 講座の受講期限設定（固定日／相対日数）を取得
     *
     * @return HasOne<CourseDeadline, $this>
     */
    public function courseDeadline(): HasOne
    {
        return $this->hasOne(CourseDeadline::class);
    }
}
