<?php

namespace App\Services\Course;

use App\Model\Course;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Enums\Course\StatusEnum;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use Illuminate\Support\Facades\DB;

class CopyService
{
    /**
     * 講座を丸ごと複製して新規作成する
     *
     * @return Course 新しく作成された講座
     */
    public function __invoke(Course $originalCourse): Course
    {
        return DB::transaction(function () use ($originalCourse) {

            // ① Course を複製
            $newCourse = $originalCourse->replicate([
                'title',
                'image',
                'deadline_type',
                'capacity',
                'instructor_id',
            ]);

            // タイトルだけ「 - コピー」を付与
            $newCourse->title = $originalCourse->title . ' - コピー';

            // ステータスは下書き
            $newCourse->status = StatusEnum::DRAFT;

            $newCourse->save();

            // ② タグ（多対多）をコピー
            if ($originalCourse->tags()->exists()) {
                $newCourse->tags()->sync($originalCourse->tags->pluck('id'));
            }

            // ③ courseDeadline（1:1）をコピー
            if ($originalCourse->courseDeadline()->exists()) {
                $newCourse->courseDeadline()->create(
                    $originalCourse->courseDeadline->replicate()->toArray()
                );
            }

            // ④ チャプターとレッスンをコピー
            foreach ($originalCourse->chapters as $chapter) {

                // チャプター複製
                $newChapter = $chapter->replicate([
                    'order',
                    'title',
                ]);

                $newChapter->course_id = $newCourse->id;
                $newChapter->status = ChapterStatusEnum::DRAFT;
                $newChapter->save();

                // レッスン複製
                foreach ($chapter->lessons as $lesson) {
                    $newLesson = $lesson->replicate([
                        'title',
                        'url',
                        'remarks',
                        'order',
                    ]);

                    $newLesson->chapter_id = $newChapter->id;
                    $newLesson->status = LessonStatusEnum::DRAFT;
                    $newLesson->save();
                }
            }

            return $newCourse;
        });
    }
}
