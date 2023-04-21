<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'courses';

    protected $fillable = [
        'title',
        'image'
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
        return $this->hasMany(Chapter::class);
    }
}
