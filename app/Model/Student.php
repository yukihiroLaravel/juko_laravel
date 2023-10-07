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
        'birth_date',
        'sex',
        'address',
    ];

    /**
     * キャスト
     */
    protected $casts = [
        'birth_date' => 'date',
        'last_login_at' => 'date',
    ];

    // 性別定数
    const SEX_MAN = 'man';
    const SEX_WOMAN = 'woman';
    const SEX_MAN_INT = 1;
    const SEX_WOMAN_INT = 2;
    const SEX_UNKNOWN_INT = 0;

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
     * お知らせを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'viewed_once_notifications', 'student_id', 'notification_id')->withTimestamps();
    }

    /**
     * 受講状況を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
        public function attendance()
    {
        return $this->hasMany(Attendance::class);
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
     * 文字列の性別を数値に変換
     *
     * @param string $sex
     * @return int
     */
   
    public function getSexAttribute($value)
    {
        if ($value === self::SEX_MAN_INT) {
            return self::SEX_MAN;
        } elseif ($value === self::SEX_WOMAN_INT) {
            return self::SEX_WOMAN;
        }

        return null;
    }

    public function setSexAttribute($value)
    {
        $this->attributes['sex'] = null;

        if ($value === self::SEX_MAN) {
            $this->attributes['sex'] = self::SEX_MAN_INT;
        } elseif ($value === self::SEX_WOMAN) {
            $this->attributes['sex'] = self::SEX_WOMAN_INT;
        }
    }

     /**
     * フルネームアクセサー
     */
    public function getFullNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }
}
