<?php

namespace App\Model;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $given_name_by_instructor
 * @property string $nick_name
 * @property string $last_name
 * @property string $first_name
 * @property string $occupation
 * @property string $email
 * @property string $password
 * @property string $purpose
 * @property Carbon $birth_date
 * @property int $gender
 * @property string $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $last_login_at
 * @property string $email_verified_at
 * @property string $profile_image
 * @property string $full_name
 * @property Collection<Course> $courses
 * @property Collection<Notification> $notifications
 * @property Collection<Attendance> $attendances
 */
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
     * キャスト
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
     * @param $brithDay
     * @param $today
     * @return $calcAge
     */
    public static function calcAge($birthDay, $today)
    {
        $calcAge = $birthDay->diffInYears($today);
        return $calcAge;
    }
}
