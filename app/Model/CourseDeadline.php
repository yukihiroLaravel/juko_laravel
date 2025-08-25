<<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CourseDeadline extends Model
{
    protected $table = 'course_deadlines';

    protected $fillable = [
        'course_id',
        'fixed_date',     // date|null
        'relative_days',  // int|null（「受講開始からN日」）
    ];

    protected $casts = [
        'course_id'     => 'int',
        'fixed_date'    => 'date',    // Carbon\CarbonImmutable相当のdate扱い
        'relative_days' => 'int',
        'created_at'    => 'immutable_datetime',
        'updated_at'    => 'immutable_datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}