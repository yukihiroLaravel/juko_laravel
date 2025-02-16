<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property bool $has_active_students
 */
class Course extends Model
{
    use SoftDeletes;
    use HasFactory;

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
        'created_at',
        'updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'instructor_id' => 'int',
    ];

    /**
     * モデルのブート処理
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

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
}
