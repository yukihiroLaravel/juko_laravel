<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Course;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CopyService
{
    /**
     * 講座複製サービス
     *
     * @param Course $course
     * @param int $instructorId
     * @return Course
     */
    public function __invoke(Course $course, int $instructorId): Course
    {
        // /** @var Course|null $original */
        // $original = Course::with(['chapters.lessons', 'tags'])->find($courseId);

        // if ($original === null) {
        //     throw new NotFoundHttpException('Course not found.');
        // }

        // リレーションをロード
        // $course->load(['chapters.lessons', 'tags']);
        $course->load([
            'chapters',
            'chapters.lessons',
            'tags',
        ]);
        // dd($course->chapters);

        // トランザクション開始
        return DB::transaction(function () use ($course, $instructorId) {

            // ① 講座名を「 - コピー」付きで生成
            $newTitle = $course->title . ' - コピー';

            // ② Course を複製（created_at はモデルの boot() により自動で現在時刻）
            /** @var Course $newCourse */
            $newCourse = Course::create([
                'instructor_id' => $instructorId,
                'title' => $newTitle,
                'image' => $course->image, // 画像はそのままコピー
                'status' => CourseStatusEnum::DRAFT->value,
                'deadline_type' => $course->deadline_type,
                'capacity' => $course->capacity,
            ]);

            // ③ タグを複製（中間テーブル）
            $tagIds = $course->tags->pluck('id')->all();
            if (!empty($tagIds)) {
                $newCourse->tags()->attach($tagIds);
            }

            // ④ チャプター複製
            foreach ($course->chapters as $chapter) {

                $newChapter = $newCourse->chapters()->create([
                    'order' => $chapter->order,
                    'title' => $chapter->title,
                    'status' => ChapterStatusEnum::DRAFT->value,
                ]);

                // ⑤ レッスン複製
                foreach ($chapter->lessons as $lesson) {
                    $newChapter->lessons()->create([
                        'title' => $lesson->title,
                        'url' => $lesson->url,
                        'remarks' => $lesson->remarks,
                        'order' => $lesson->order,
                        'status' => LessonStatusEnum::DRAFT->value,
                    ]);
                }
            }

            return $newCourse;
        });
    }
}
