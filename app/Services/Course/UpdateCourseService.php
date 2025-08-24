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
        Course $course,
        string $title,
        ?UploadedFile $imageFile,
        string $status,
        string $deadlineType,
        ?string $fixedDate = null,
        ?int $relativeDays = null
    ): void {
        $imagePath = $this->getImagePath($course, $imageFile);
        // 講座を更新
        $course->update([
            'title' => $title,
            'image' => $imagePath,
            'status' => $status,
            'deadline_type' => $deadlineType ?? 'none',
        ]);

        $hasDeadline = in_array($deadlineType, ['fixed_date', 'relative_days'], true);

        if (!$hasDeadline) {
            $course->deadline()->delete();
            return;
        }

        $course->deadline()->updateOrCreate(
            ['course_id' => $course->id],
            [
                'fixed_date' => $deadlineType === 'fixed_date' ? $fixedDate : null,
                'relative_days' => $deadlineType === 'relative_days' ? $relativeDays : null,
            ]
        );
    }

    /**
     * 画像パスを取得する
     */
    private function getImagePath(Course $course, ?UploadedFile $imageFile): string
    {
        // 画像ファイルがアップロードされた場合の処理
        if ($imageFile) {
            // 既存の画像ファイルを削除（存在する場合のみ）
            if ($course->image && Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }
            // 新しい画像ファイルを保存
            $extension = $imageFile->getClientOriginalExtension();
            $filename = Str::uuid()->toString().'.'.$extension;
            $imagePath = Storage::putFileAs('public/course', $imageFile, $filename);

            return Course::convertImagePath($imagePath);
        }

        // 画像ファイルがない場合は既存の画像パスを使用
        return $course->image;
    }
}
