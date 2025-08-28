<?php

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(
        string $title,
        UploadedFile $image,
        int $tagId,
        int $instructorId,
        DeadlineTypeEnum $deadlineType,
        ?string $fixedDate = null,
        ?int $relativeDays = null
    ): Course {
        // ファイルパスを作成
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $image, $filename);
        $filePath = Course::convertImagePath($filePath);

        // 講座を作成
        /** @var Course $course */
        $course = Course::create([
            'instructor_id' => $instructorId,
            'title' => $title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
            'deadline_type' => $deadlineType->value,
        ]);

        // course_deadlines に保存（どちらか一方）
        if (in_array($deadlineType, [
            DeadlineTypeEnum::FIXED_DATE,
            DeadlineTypeEnum::RELATIVE_DAYS,
        ], true)) {
            $course->courseDeadline()->create([
                'fixed_date' => $deadlineType === DeadlineTypeEnum::FIXED_DATE ? $fixedDate : null,
                'relative_days' => $deadlineType === DeadlineTypeEnum::RELATIVE_DAYS ? $relativeDays : null,
            ]);
        }

        // ログイン中の講師が作成したタグかどうか確認
        $tag = Tag::where('id', $tagId)
            ->where('instructor_id', $instructorId)
            ->first();

        if ($tag === null) {
            throw new NotFoundHttpException('Not Found Tag.');
        }

        // タグを中間テーブルに紐づける
        $course->tags()->attach($tag->id);

        return $course;
    }
}
