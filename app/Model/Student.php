<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{

    const SEX_MAN = 'man';
    const SEX_WOMAN = 'woman';
    const SEX_MAN_INT = 1;
    const SEX_WOMAN_INT = 2;
    const SEX_UNKNOWN_INT = 0;

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
        if ($sex === self::SEX_MAN) {
            return self::SEX_MAN_INT;
        } elseif ($sex === self::SEX_WOMAN) {
            return self::SEX_WOMAN_INT;
        } else {
            return self::SEX_UNKNOWN_INT;
        }
    }
}
