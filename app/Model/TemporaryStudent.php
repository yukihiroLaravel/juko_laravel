<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Student\Gender;


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
        'gender' => Gender::class,
    ];

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
}
