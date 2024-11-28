<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * フルネームアクセサー
     */
    public function getFullNameAttribute()
    {
        return $this->last_name.' '.$this->first_name;
    }
}
