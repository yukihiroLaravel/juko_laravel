<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->belongsTo(Student::class);
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

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($course) {
            foreach ($course->chapters()->get() as $child) {
                $child->delete();
            }
        });
    }
}
