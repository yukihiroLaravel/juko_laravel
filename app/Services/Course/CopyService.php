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
    private const string TITLE_SUFFIX = ' - コピー';

    private const int TITLE_MAX_LENGTH = 50;

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

        // 複製元と同じファイルを指すと、片方の講座を削除したときにもう片方の画像まで消える
        $copiedImagePath = 'course/'.Str::uuid()->toString().'.'.pathinfo($course->image, PATHINFO_EXTENSION);

        /** @var Course $newCourse */
        $newCourse = Course::create([
            'instructor_id' => $course->instructor_id,
            'title' => $this->copiedTitle($course->title),
            'image' => $copiedImagePath,
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

        // 登録が失敗したときに実体だけが残らないよう、ファイルの複製は最後に行う
        $disk = Storage::disk('public');
        if ($disk->exists($course->image)) {
            $disk->copy($course->image, $copiedImagePath);
        }

        $newCourse->load(['chapters.lessons', 'courseDeadline']);

        return $newCourse;
    }

    /**
     * 複製した講座の講座名を返す
     *
     * 講座名の上限に収まるよう、複製元の講座名の末尾を切り詰めてから接尾辞を付ける。
     */
    private function copiedTitle(string $title): string
    {
        $maxLength = self::TITLE_MAX_LENGTH - mb_strlen(self::TITLE_SUFFIX);

        return mb_substr($title, 0, $maxLength).self::TITLE_SUFFIX;
    }
}
