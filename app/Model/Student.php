<?php

namespace App\Model;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Enums\Student\Gender;

class Student extends Authenticatable
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'students';

    /**
     * @var array<int, string>
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
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'gender' => Gender::class,
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
        // データベースのgenderカラムの数値を Gender enumに変換し、対応するラベル（文字列）を返す
        return Gender::from($value)->label();
    }

    public function setGenderAttribute($value)
    {
        // ユーザー入力の値（$value）に基づき Gender enumのインスタンスを決定
        $gender = match ($value) {
            'man' => Gender::MAN, // 'man' の場合は Gender::MAN に対応
            'woman' => Gender::WOMAN,
            default => Gender::UNKNOWN,
        };
    
        // $genderはGender::MAN 等に対応していてこれらのインスタンスはenumのcaseで数値が定義されているので$gender->valueは対応する数値になる。
        $this->attributes['gender'] = $gender->value;
    }
    
    /**
     * フルネームアクセサー
     */
    public function getFullNameAttribute()
    {
        return $this->last_name.' '.$this->first_name;
    }

    /**
     * 画像保存パスに変換
     *
     * @return string
     */
    public static function convertImagePath(string $filePath)
    {
        // public/を削除
        return str_replace('public/', '', $filePath);
    }

    /**
     * 年齢計算
     */
    public function calcAge(CarbonImmutable $today): int
    {
        return (int) $this->birth_date->diffInYears($today);
    }
}
