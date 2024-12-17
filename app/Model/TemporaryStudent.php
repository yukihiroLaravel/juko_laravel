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
     * フルネームアクセサー
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_name.' '.$this->first_name,
        );
    }
}
