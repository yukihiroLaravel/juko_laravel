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
    public function __invoke(string $title, UploadedFile $image, int $tagId, int $instructorId, ?string $attendanceDeadline = null): Course
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
            'attendance_deadline' => $attendanceDeadline,
        ]);

        // ログイン中の講師が作成したタグかどうか確認
        $tag = Tag::where('id', $tagId)
            ->where('instructor_id', $instructorId)
            ->first();
        if ($tag === null) {
            throw new NotFoundHttpException('Not Found Tag.');
        }

        // タグを中間テーブルに紐づける
        $course->tags()->attach($tagId);

        return $course;
    }
}
