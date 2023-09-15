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

    protected $fillable = [
        'given_name_by_instructor',
        'email',
        'created_at',
        'updated_at',
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
    const MAN = 'man';
    const WOMAN = 'woman';
    
    public function getSexAttribute($value)
    {    
        if ($value === self::SEX_MAN) {
            return self::MAN;
        } elseif ($value === self::SEX_WOMAN) {
            return self::WOMAN;
        }
        return null;
    }

    /**
     * キャスト
     */
    protected $casts = [
        'birth_date' => 'date',
        'last_login_at' => 'date',
    ];
}
