<?php

namespace App\Model;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'notifications'; 

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'status',
        'type',
        'start_date',
        'end_date',
        'content',
    ];

    // ソート項目 定数
    const SORT_BY_TITLE = 'title';

    const SORT_BY_COURSE_ID = 'course_id';

    const SORT_BY_START_DATE = 'start_date';

    /**
     * 受講生を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'viewed_once_notifications', 'notification_id', 'student_id')->withTimestamps();
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * 講師を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
            'type' => TypeEnum::class,
        ];
    }

    /**
     * スコープ: 公開中のお知らせを取得
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', StatusEnum::PUBLIC);
    }

    /**
     * スコープ: 既読データ(read)/未読データ(unread)判別
     */
    public function scopeFilterByReadStatus($query, string $filter, int $studentId)
    {
        return match ($filter) {
            'read' => $query->whereHas('students', fn ($q) => $q->where('student_id', $studentId)),
            'unread' => $query->whereDoesntHave('students', fn ($q) => $q->where('student_id', $studentId)),
            default => $query,
        };
    }
}
