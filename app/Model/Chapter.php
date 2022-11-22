<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'chapters';

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * レッスンを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lesson()
    {
        return $this->hasMany(Lesson::class);
    }
}
