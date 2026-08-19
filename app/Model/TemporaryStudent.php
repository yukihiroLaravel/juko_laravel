<?php

namespace App\Model;

use App\Enums\Student\GenderEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $full_name
 */
class TemporaryStudent extends Model
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'temporary_students';

    /**
     * @var list<string>
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
     * シリアライズから除外する属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'code',
        'token',
    ];

    /**
     * フルネームアクセサー
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(get: fn () => $this->last_name.' '.$this->first_name);
    }

    /**
     * @return array{
     *  birth_date: 'immutable_date',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime',
     *  expire_at: 'immutable_datetime',
     *  gender: 'App\Enums\Student\GenderEnum'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'expire_at' => 'immutable_datetime',
            'gender' => GenderEnum::class,
        ];
    }
}
