<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $course_id
 * @property int $order
 * @property string $title
 * @property 'public'|'private' $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $completed_count
 * @property Course $course
 * @property Collection<Lesson> $lessons
 */
class Chapter extends Model
{
    use SoftDeletes;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'chapters';

    // ステータス定数
    const STATUS_PUBLIC = 'public';
    const STATUS_PRIVATE = 'private';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'chapter_id',
        'course_id',
        'order',
        'title',
        'status'
    ];

    /**
     * モデルのブート時の処理
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // チャプター削除時に紐づくレッスンも削除
        static::deleting(function (Chapter $chapter) {
            $chapter->lessons()->delete();
        });

        // チャプター更新時に紐づくレッスンも更新（ステータスが非公開の場合）
        static::updating(function (Chapter $chapter) {
            if ($chapter->status === Chapter::STATUS_PRIVATE) {
                $chapter->lessons()->update(['status' => Lesson::STATUS_PRIVATE]);
            }
        });
    }

    /**
     * 講座を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * レッスンを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order', 'asc');
    }

    /**
     * 公開中のチャプターを抽出
     *
     * @param \Illuminate\Support\Collection $chapters
     * @return \Illuminate\Support\Collection
     */
    public static function extractPublicChapter($chapters)
    {
        return $chapters->filter(function ($chapter) {
            return $chapter->status === Chapter::STATUS_PUBLIC;
        });
    }

    /**
     * チャプターのステータスを一括更新
     *
     * @param int $courseId
     * @param 'public'|'private' $status
     * @return void
     */
    public static function chapterUpdateAll(int $courseId, string $status): void
    {
        Chapter::where('course_id', $courseId)
            ->update([
                'status' => $status
            ]);
    }

    /**
     * チャプターの進捗計算
     *
     * @param Attendance  $attendance
     * @return array
     */
    public function calculateChapterProgress(Attendance $attendance): int
    {
        $completedLessonsCount = $this->calculateCompletedLessonCount($this, $attendance);
        $totalLessonsCount = $this->lessons->count();
        return $totalLessonsCount > 0 ? ($completedLessonsCount / $totalLessonsCount) * 100 : 0;
    }

    /**
     * チャプター内完了済みレッスン数計算
     *
     * @param Chapter $chapter
     * @param Attendance $attendance
     * @return int
     */
    private function calculateCompletedLessonCount(Chapter $chapter, Attendance $attendance): int
    {
        return $chapter->lessons->filter(function (Lesson $lesson) use ($attendance) {
            $lessonAttendance = $lesson->lessonAttendances->firstWhere('attendance_id', $attendance->id);
            return $lessonAttendance && $lessonAttendance->status === LessonAttendance::STATUS_COMPLETED_ATTENDANCE;
        })
        ->count();
    }

}
