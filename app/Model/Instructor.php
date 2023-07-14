<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'instructors';

    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'email',
    ];

    /**
     * @var array<string>
     */
    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'email'
    ];

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
