<?php

namespace App\Model;

use Carbon\CarbonImmutable;
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'instructor_id' => 'int',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * 講座を取得
     *
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tag', 'tag_id', 'course_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            $tag->created_at = CarbonImmutable::now();
            $tag->updated_at = CarbonImmutable::now();
        });

        static::updating(function (Tag $tag) {
            $tag->updated_at = CarbonImmutable::now();
        });
    }
}
