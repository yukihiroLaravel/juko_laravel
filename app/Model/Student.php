<?php

namespace App\Model;

use App\Enums\Student\Gender;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property CarbonImmutable|null $latest_login_at
 * @property string|null $attendance_deadline
 * @property string|null $expires_at
 * @property int|null $days_until_expiry
 */
class Student extends Authenticatable
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'students';

    /**
     * @var list<string>
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
        'last_login_at',
    ];

    /**
     * Get the remember token value.
     */
    #[\Override]
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * Set the remember token value.
     *
     * @param  string  $value
     */
    #[\Override]
    public function setRememberToken($value): void
    {
        // Do nothing.
    }

    /**
     * Get the name of the remember token.
     */
    #[\Override]
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * 講座を取得
     *
     * @return HasMany<Course, $this>
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * お知らせを取得
     *
     * @return BelongsToMany<Notification, $this>
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'viewed_once_notifications', 'student_id', 'notification_id')->withTimestamps();
    }

    /**
     * ログイン履歴を取得
     *
     * @return HasMany<StudentLoginHistory, $this>
     */
    public function loginHistories()
    {
        return $this->hasMany(StudentLoginHistory::class);
    }

    /**
     * 受講履歴を取得
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * フルネームアクセサー
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(get: fn () => $this->last_name.' '.$this->first_name);
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

    /**
     * @return array{
     *  birth_date: 'immutable_date',
     *  last_login_at: 'immutable_datetime',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime',
     *  gender: 'App\Enums\Student\Gender'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'last_login_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'gender' => Gender::class,
        ];
    }
}
