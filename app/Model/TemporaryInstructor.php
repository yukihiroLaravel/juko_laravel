<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $full_name
 */
class TemporaryInstructor extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'temporary_instructors';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'manager_id',
        'trial_count',
        'code',
        'token',
        'expire_at',
        'nick_name',
        'last_name',
        'first_name',
        'email',
        'type',
    ];

    protected $casts = [
        'expire_at' => 'immutable_datetime',
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
