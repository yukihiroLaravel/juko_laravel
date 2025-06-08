<?php

namespace app\Services\Course;

use App\Model\Course;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateCourseService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(string $title, UploadedFile $imageFile, string $courseImage, int $instructorId): void
    {
        // ファイルパスを作成
        $file = $imageFile;
        if (isset($file)) {
            // 更新前の画像ファイルを削除
            if (Storage::disk('public')->exists($courseImage)) {
                Storage::disk('public')->delete($courseImage);
            }

            // 画像ファイル保存処理
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString().'.'.$extension;
            $imagePath = Storage::putFileAs('public/course', $file, $filename);
            if (! $imagePath) {
                throw new \RuntimeException('ファイル保存に失敗しました。');
            }
            $imagePath = Course::convertImagePath($imagePath);
        }

        // 講座を作成
        $course->Update([
            'instructor_id' => $instructorId,
            'title' => $title,
            'image' => $imagePath,
            'status' => Course::STATUS_PRIVATE,
        ]);
    }
}