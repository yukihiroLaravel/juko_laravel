<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StudentAuthorization extends Model
{
     /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'student_authorization';

     /**
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'trial_count',
        'code',
        'expire_at',
    ];

    //認証テーブルのcreated_atとupdated_atの更新を無効化する
    public $timestamps = false;
}