<?php

namespace App\Model;

use Carbon\CarbonImmutable;
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
     * @return array{
     *  fixed_date: 'immutable_date'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'fixed_date' => 'immutable_date',
        ];
    }

    /**
     * 講座とのリレーション
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 受講期限を当日 23:59:59 に揃えて返す
     */
    public function getFixedDeadlineEndAttribute(): ?CarbonImmutable
    {
        return $this->fixed_date ? $this->fixed_date->endOfDay() : null;

    }
}
