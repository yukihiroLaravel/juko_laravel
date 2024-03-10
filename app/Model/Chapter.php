<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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
        static::deleting(function ($chapter) {
            foreach ($chapter->lessons()->get() as $child) {
                $child->delete();
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

    public static function chapterupdate($request)
    {
        $course = Course::findOrFail($request->course_id);

        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
            return response()->json([
                'result' => false,
                "message" => "Not authorized."
            ], 403);
        }
        Chapter::where('course_id', $request->course_id)
            ->update([
                'status' => $request->status
            ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
