<?php

namespace App\Model;

use App\Enums\Course\DeadlineTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDeadline extends Model
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'course_deadlines';

    /**
     * @var list<string>
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
     * 受講期限設定があるかどうか
     */
    public static function hasDeadline(DeadlineTypeEnum $deadlineType): bool
    {
        return in_array($deadlineType, [
            DeadlineTypeEnum::FIXED_DATE,
            DeadlineTypeEnum::RELATIVE_DAYS,
        ], true);
    }
}
