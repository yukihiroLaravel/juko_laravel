<?php

namespace App\Model;

use App\Model\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDeadline extends Model
{
    /**
    * モデルと関連しているテーブル
    *
    * @var string
    */
    protected $table = 'course_deadlines';

    /**
    * @var array<int, string>
    */
    protected $fillable = [
        'course_id',
        'fixed_date',
        'relative_days',
    ];

    /**
    * 講座とのリレーション
    *
    * @return BelongsTo<Course, self>
    */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
