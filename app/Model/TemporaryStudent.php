<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $full_name
 */
class TemporaryStudent extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'temporary_students';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'trial_count',
        'code',
        'token',
        'expire_at',
        'nick_name',
        'last_name',
        'first_name',
        'email',
        'occupation',
        'purpose',
        'birth_date',
        'gender',
        'address',
    ];

    /**
     * キャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 性別定数
    const GENDER_MAN = 'man';

    const GENDER_WOMAN = 'woman';

    const GENDER_MAN_INT = 1;

    const GENDER_WOMAN_INT = 2;

    const GENDER_UNKNOWN_INT = 0;

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
        return $this->last_name.' '.$this->first_name;
    }
}
