<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{

    const SEX_MAN = 'man';
    const SEX_WOMAN = 'woman';

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'students';

    /**
     *
     * @var array
     */
    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'occupation',
        'email',
        'password',
        'purpose',
        'birthdate',
        'sex',
        'address',
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
     * 文字列の性別を数値に変換
     *
     * @param string $sex
     * @return int
     */
    public static function convertSexToInt($sex)
    {
        return $sex === self::SEX_MAN ? 1 : ($sex === self::SEX_WOMAN ? 2 : 0);
    }
}
