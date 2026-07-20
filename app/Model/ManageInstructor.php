<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageInstructor extends Model
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'manage_instructors';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'instructor_id',
        'manager_id',
    ];
}
