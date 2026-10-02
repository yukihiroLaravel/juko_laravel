<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $full_name
 */
class TemporaryInstructor extends Model
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'temporary_instructors';

    /**
     * @var list<string>
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
        return Attribute::make(
            get: fn () => $this->last_name.' '.$this->first_name,
        );
    }

    /**
     * @return array{
     *  expire_at: 'immutable_datetime'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'expire_at' => 'immutable_datetime',
        ];
    }
}
