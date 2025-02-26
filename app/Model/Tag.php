<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tag extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'tags';

    // Laravelの自動タイムスタンプ機能（created_at, updated_at が自動管理(now)される）
    public $timestamps = true;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'instructor_id',
        'content',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'instructor_id' => 'int',
    ];

    /**
     * 講座を取得
     *
     * @return BelongsTo<Course, $this>
     */
    public function courses(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_tag', 'tag_id', 'course_id');
    }
}
