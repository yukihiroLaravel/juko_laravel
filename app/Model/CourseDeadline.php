<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CourseDeadline extends Model
{
    protected $table = 'course_deadlines';
    public $timestamps = true; // created_at / updated_at あり

    protected $fillable = [
        'course_id',
        'fixed_date',
        'relative_days',
    ];

    protected $casts = [
        'fixed_date'    => 'date:Y-m-d',
        'relative_days' => 'int',
        'created_at'    => 'immutable_datetime',
        'updated_at'    => 'immutable_datetime',
    ];
}
