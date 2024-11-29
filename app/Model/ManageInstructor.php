<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ManageInstructor extends Model
{
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'manage_instructors';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'instructor_id',
        'manager_id',
    ];
}
