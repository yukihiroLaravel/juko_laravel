<?php

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\Attendance;
use App\Services\Attendance\CalculateDeadlineService; 
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class UpdateService
{
    /**
     * 講座登録サービス
     */
    public function __invoke(
        Course $course,
        string $title,
        ?UploadedFile $imageFile,
        string $status,
        DeadlineTypeEnum $deadlineType,
        ?string $fixedDate = null,
        ?int $relativeDays = null
    ): void {
        $imagePath = $this->getImagePath($course, $imageFile);
        // 講座を更新
        $course->update([
            'title' => $title,
            'image' => $imagePath,
            'status' => $status,
            'deadline_type' => $deadlineType->value,
        ]);

        if (! $this->hasDeadline($deadlineType)) {
            $course->courseDeadline()->delete();
            
            // コースの受講期限を削除
            Attendance::where('course_id', $course->id)->update(['attendance_deadline' => null]);

            return;
        }

        $deadline = $course->courseDeadline()->updateOrCreate(
            ['course_id' => $course->id],
            [
                'fixed_date' => $deadlineType === DeadlineTypeEnum::FIXED_DATE ? $fixedDate : null,
                'relative_days' => $deadlineType === DeadlineTypeEnum::RELATIVE_DAYS ? $relativeDays : null,
            ]
        );

        // === Attendance の期限も更新 === 
        $calculateDeadline = app(CalculateDeadlineService::class); 

        $attendances = Attendance::where('course_id', $course->id)->get(); 
        
        foreach ($attendances as $attendance) { 
            $startAt = new DateTimeImmutable($attendance->created_at); 
            $newDeadline = $calculateDeadline( 
                $deadlineType->value, 
                $deadline->fixed_date ? new DateTimeImmutable($deadline->fixed_date) : null, 
                $deadline->relative_days, 
                $startAt 
            );

            $attendance->update([
                'attendance_deadline' => $newDeadline?->format('Y-m-d'),
            ]);
        }
    }

    /**
     * 受講期限設定があるかどうか
     */
    private function hasDeadline(DeadlineTypeEnum $deadlineType): bool
    {
        return in_array($deadlineType, [
            DeadlineTypeEnum::FIXED_DATE,
            DeadlineTypeEnum::RELATIVE_DAYS,
        ], true);
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
