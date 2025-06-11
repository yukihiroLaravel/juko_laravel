<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateCourseService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(
        Course $course, string $title, ?UploadedFile $imageFile, string $status
    ): void {

        if (isset($imageFile)) {
            // 更新前の画像ファイルを削除
            Storage::disk('public')->exists($course->image);
            Storage::disk('public')->delete($course->image);

            // 画像ファイル保存処理
            $extension = $imageFile->getClientOriginalExtension();
            $filename = Str::uuid()->toString().'.'.$extension;
            $imagePath = Storage::putFileAs('public/course', $imageFile, $filename);
            $imagePath = Course::convertImagePath($imagePath);
        }

        // 画像ファイルがnullの場合は、既存の画像パスを使用
        if (!isset($imageFile)) {
            $imagePath = $course->image;
        }

        // 講座を更新
        $course->update([
            'title' => $title,
            'image' => $imagePath,
            'status' => $status,
        ]);
    }
}
