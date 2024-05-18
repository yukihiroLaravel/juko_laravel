<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $instructor_id
 * @property string $title
 * @property string $image
 * @property 'public'|'private' $status
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 * @property Instructor $instructor
 * @property Collection<Chapter> $chapters
 * @property Collection<Attendance> $attendances
 */
class Course extends Model
{
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

    protected $fillable = [
        'instructor_id',
        'title',
        'image',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'instructor_id' => 'int'
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * 受講状態を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * チャプターリストを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('order', 'asc');
    }

    /**
     * 画像保存パスに変換
     *
     * @param string $filePath
     * @return string
     */
    public static function convertImagePath(string $filePath)
    {
        // public/を削除
        return str_replace('public/', '', $filePath);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
