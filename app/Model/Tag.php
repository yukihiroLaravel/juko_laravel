<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'tags';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'instructor_id',
        'content',
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
     * 講座を取得
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tag', 'tag_id', 'course_id');
    }
}
