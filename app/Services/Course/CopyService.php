<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Course;
use App\Model\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CopyService
{
    /**
     * 講座複製サービス
     */
    public function __invoke(Course $course, int $instructorId): Course
    {
        $course->load([
            'chapters',
            'chapters.lessons',
            'tags',
        ]);

        // 該当する講座を所有する講師が作成したタグなら複製する
        $tagIds = $course->tags->pluck('id')->all();

        if ($tagIds !== []) {

            $ownedCount = Tag::whereIn('id', $tagIds)
                ->where('instructor_id', $course->instructor_id)
                ->count();

            if ($ownedCount !== count($tagIds)) {
                throw new NotFoundHttpException('Not Found Tag.');
            }
        }

        // 講座名を「 - コピー」付きで生成
        $newTitle = $course->title.' - コピー';

        // 複製した講座の画像ファイルパスを作成
        $image = $course->image;
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $copiedPath = 'course/'.Str::uuid()->toString().'.'.pathinfo($course->image, PATHINFO_EXTENSION);
        Storage::disk('public')->copy($course->image, $copiedPath);

        // Course を複製（created_at はモデルの boot() により自動で現在時刻）
        /** @var Course $newCourse */
        $newCourse = Course::create([
            'instructor_id' => $course->instructor_id,
            'title' => $newTitle,
            'image' => $copiedPath,
            'status' => CourseStatusEnum::DRAFT->value,
            'deadline_type' => 'none',
            'capacity' => null,
        ]);

        // 該当する講座を所有する講師が作成したタグなら複製する
        $tagIds = $course->tags->pluck('id')->all();

        if ($tagIds !== []) {
            // タグを複製（中間テーブル）
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
    }
}
