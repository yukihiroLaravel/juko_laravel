<?php

namespace App\Services\Course;

use App\Model\Course;
use App\Model\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreCourseService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(string $title, UploadedFile $image, int $tagId, int $instructorId,?string $deadlineType = null, ?string $fixedDate = null, ?int $relativeDays = null): Course
    {
        // ファイルパスを作成
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $image, $filename);
        $filePath = Course::convertImagePath($filePath);

        // 講座を作成
        $course = Course::create([
            'instructor_id' => $instructorId,
            'title' => $title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
            'deadline_type' => $deadlineType ?? 'none',
        ]);

        // course_deadlines に保存（どちらか一方）
        if (in_array($deadlineType, ['fixed_date', 'relative_days'], true)) {
            $course->deadline()->create([
                'fixed_date' => $deadlineType === 'fixed_date' ? $fixedDate : null,
                'relative_days' => $deadlineType === 'relative_days' ? $relativeDays : null,
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
