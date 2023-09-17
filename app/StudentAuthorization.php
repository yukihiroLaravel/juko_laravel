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

    protected $fillable = [
        'student_id',
        'number_of_attempts',
        'authentication_code',
        'verification_code_validity_period',
    ];
}