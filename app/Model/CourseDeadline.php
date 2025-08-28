<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CourseDeadline extends Model
{
    protected $table = 'course_deadlines';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'fixed_date',
        'relative_days',
    ];
}
