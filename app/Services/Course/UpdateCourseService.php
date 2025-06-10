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
        Course $course, string $title, UploadedFile $imageFile, string $status
    ): void {
        // ファイルパスを作成
        $file = $imageFile;
        if (isset($file)) {
            // 更新前の画像ファイルを削除
            if (Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }

            // 画像ファイル保存処理
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString().'.'.$extension;
            $imagePath = Storage::putFileAs('public/course', $file, $filename);
            $imagePath = Course::convertImagePath($imagePath);
        }

        // 講座を作成
        $course->Update([
            'title' => $title,
            'image' => $imagePath,
            'status' => $status,
        ]);
    }
}
