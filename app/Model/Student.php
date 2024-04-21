<?php

namespace App\Model;

use Carbon\Carbon;
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
 * @property int $sex
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
        'sex',
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
}
