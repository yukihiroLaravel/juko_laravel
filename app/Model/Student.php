<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'students';

    /**
     * 一括代入が許可される属性
     *
     * @var array
     */
    protected $fillable = [
        'nick_name','last_name','first_name','occupation','email','password','purpose','birthdate','sex','address',
        // 他の属性もここに追加する
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
}
