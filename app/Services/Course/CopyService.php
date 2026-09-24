<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Course;
use App\Model\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $course->load([
            'chapters',
            'chapters.lessons',
            'tags',
        ]);

        // トランザクション開始
        // return DB::transaction(function () use ($course, $instructorId) {

            // 講座名を「 - コピー」付きで生成
            $newTitle = $course->title . ' - コピー';

            // 複製した講座の画像ファイルパスを作成
            $image = $course->image;
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $copiedPath = 'course/'.Str::uuid()->toString().'.'.pathinfo($course->image, PATHINFO_EXTENSION);
            Storage::disk('public')->copy($course->image, $copiedPath);

            // Course を複製（created_at はモデルの boot() により自動で現在時刻）
            /** @var Course $newCourse */
            $newCourse = Course::create([
                'instructor_id' => $instructorId,
                'title' => $newTitle,
                'image' => $copiedPath,
                'status' => CourseStatusEnum::DRAFT->value,
                'deadline_type' => 'none',
                'capacity' => null,
            ]);

            // ログイン中の講師が作成したタグかどうか確認
            $tagIds = $course->tags->pluck('id')->all();
            $tag = Tag::where('id', $tagIds)
                ->where('instructor_id', $instructorId)
                ->first();

            // タグが存在しない場合はエラーを返す
            if ($tag === null) {
                throw new NotFoundHttpException('Not Found Tag.');
            }

            // タグを複製（中間テーブル）
            // $tagIds = $course->tags->pluck('id')->all();
            if (!empty($tagIds)) {
                $newCourse->tags()->attach($tagIds);
            }

            // チャプター複製
            foreach ($course->chapters as $chapter) {

                $newChapter = $newCourse->chapters()->create([
                    'order' => $chapter->order,
                    'title' => $chapter->title,
                    'status' => ChapterStatusEnum::DRAFT->value,
                ]);

                // レッスン複製
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

            $newCourse->load(['chapters.lessons', 'courseDeadline']);

            return $newCourse;
        // });
    }
}
