<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\DeadlineTypeEnum;
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
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(Course $course): Course
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

        /** @var Course $newCourse */
        $newCourse = Course::create([
            'instructor_id' => $course->instructor_id,
            'title' => $course->title.' - コピー',
            'image' => $this->copyImage($course->image),
            'status' => CourseStatusEnum::DRAFT->value,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
            'capacity' => null,
        ]);

        if ($tagIds !== []) {
            $newCourse->tags()->attach($tagIds);
        }

        foreach ($course->chapters as $chapter) {
            $newChapter = $newCourse->chapters()->create([
                'order' => $chapter->order,
                'title' => $chapter->title,
                'status' => ChapterStatusEnum::DRAFT->value,
            ]);

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

    /**
     * サムネイル画像を別のファイルとして複製し、複製先のパスを返す
     *
     * 複製元と同じファイルを指すと、片方の講座を削除したときにもう片方の画像まで消える。
     * 複製元の実体がないときも、同じ理由で複製先には別のパスを割り当てる。
     */
    private function copyImage(string $imagePath): string
    {
        $copiedPath = 'course/'.Str::uuid()->toString().'.'.pathinfo($imagePath, PATHINFO_EXTENSION);

        $disk = Storage::disk('public');
        if ($disk->exists($imagePath)) {
            $disk->copy($imagePath, $copiedPath);
        }

        return $copiedPath;
    }
}
