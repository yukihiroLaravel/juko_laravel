<?php

namespace app\Services\Course;

use App\Model\Course;
use App\Model\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use DomainException;


class CreateCourseService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(string $title, UploadedFile $image, int $tagId, int $instructorOrManagerId): Course
    {
        // ファイルパスを作成
        $file = $image;
        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('画像ファイルが無効です。');
        }

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;
        $filePath = Storage::putFileAs('public/course', $file, $filename);
        if (!$filePath) {
            throw new \RuntimeException('ファイル保存に失敗しました。');
        }

        $filePath = Course::convertImagePath($filePath);

        // 講座を作成
        $course = Course::create([
            'instructor_id' => $instructorOrManagerId,
            'title' => $title,
            'image' => $filePath,
            'status' => Course::STATUS_PRIVATE,
        ]);

        // ログイン中の講師が作成したタグかどうか確認
        $tag = Tag::where('id', $tagId)
            ->where('instructor_id', $instructorOrManagerId)
            ->firstOrFail();
        if (!$tag) {
            throw new \DomainException('指定されたタグが存在しません。');
        }

        // タグを中間テーブルに紐づける
        $course->tags()->attach($tagId);

        return $course;
    }
}
