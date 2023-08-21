<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'students';

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 受講履歴を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * 性別をmanかwomanで取得
     *
     * @return string
     */
    const SEX_MAN = 1;
    const SEX_WOMAN = 2;

    public function getFormattedSexAttribute()
    {    
        if ($this->sex === self::SEX_MAN) {
            return 'man';
        } elseif ($this->sex === self::SEX_WOMAN) {
            return 'woman';
        }
        return null;
    }

    /**
     * キャスト
     */
    protected $casts = [
        'birth_date' => 'date',
    ];
}
