<?php

namespace App\Model;

use App\Enums\Student\Gender;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'last_login_at',
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
     * ログイン履歴を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loginHistories()
    {
        return $this->hasMany(StudentLoginHistory::class);
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

    /**
     * 連続ログイン日数を取得
     *
     * 連続ログインしていない場合は null を返す
     */
    public function getLoginStreakDays(): ?int
    {
        $loginDates = $this->loginHistories()
            ->selectRaw('DATE(logged_in_at) as login_date')
            ->groupBy('login_date')
            ->orderByDesc('login_date')
            ->pluck('login_date');

        if ($loginDates->isEmpty()) {
            return null;
        }

        $today = CarbonImmutable::today()->toDateString();
        
         if ($loginDates[0] !== $today) {
            return null;
        }

        $streakDays = 1;
        $baseDate = CarbonImmutable::parse($loginDates[0]);

        for ($i = 1; $i < $loginDates->count(); $i++) {
            if ($loginDates[$i] !== $baseDate->subDays($i)->toDateString()) {
                break;
            }
            $streakDays++;
        }

        return $streakDays;
    }
}
