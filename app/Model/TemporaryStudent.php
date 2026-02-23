<?php

namespace App\Model;

use App\Enums\Student\Gender;
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
    public function getFullNameAttribute()
    {
        return $this->last_name.' '.$this->first_name;
    }

    /**
     * @return array{
     *  birth_date: 'immutable_date',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime',
     *  expire_at: 'immutable_datetime',
     *  gender: 'App\Enums\Student\Gender'
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
            'gender' => Gender::class,
        ];
    }
}
