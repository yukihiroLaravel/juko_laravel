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

    #[\Override]
    protected function casts(): array
    {
        return [
            'fixed_date'    => 'immutable_date',
            'relative_days' => 'int',
            'created_at'    => 'immutable_datetime',
            'updated_at'    => 'immutable_datetime',
        ];
    }
}
