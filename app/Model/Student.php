<?php

namespace App\Model;

use Carbon\CarbonImmutable;
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
     * @var array<string>
     */
    protected $fillable = [
        'given_name_by_instructor',
        'nick_name',
        'last_name',
        'first_name',
        'occupation',
        'email',
        'password',
        'purpose',
        'birth_date',
        'gender',
        'address',
        'profile_image',
    ];

    /**
     * @var array<string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 性別定数
    const GENDER_MAN = 'man';
    const GENDER_WOMAN = 'woman';
    const GENDER_MAN_INT = 1;
    const GENDER_WOMAN_INT = 2;
    const GENDER_UNKNOWN_INT = 0;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'viewed_once_notifications', 'student_id', 'notification_id')->withTimestamps();
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

    public function getGenderAttribute($value)
    {
        if ($value === self::GENDER_MAN_INT) {
            return self::GENDER_MAN;
        } elseif ($value === self::GENDER_WOMAN_INT) {
            return self::GENDER_WOMAN;
        }

        return null;
    }

    public function setGenderAttribute($value)
    {
        $this->attributes['gender'] = null;

        if ($value === self::GENDER_MAN) {
            $this->attributes['gender'] = self::GENDER_MAN_INT;
        } elseif ($value === self::GENDER_WOMAN) {
            $this->attributes['gender'] = self::GENDER_WOMAN_INT;
        }
    }

     /**
     * フルネームアクセサー
     */
    public function getFullNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }

    /**
     * 画像保存パスに変換
     *
     * @param string $filePath
     * @return string
     */
    public static function convertImagePath(string $filePath)
    {
        // public/を削除
        return str_replace('public/', '', $filePath);
    }

    /**
     * 年齢計算
     *
     * @param CarbonImmutable $today
     * @return int
     */
    public function calcAge($today): int
    {
        return $this->birth_date->diffInYears($today);
    }
}
