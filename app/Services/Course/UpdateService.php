<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Services\Attendance\CalculateDeadlineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

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
        CalculateDeadlineService $calculateDeadline,
        ?string $fixedDate = null,
        ?int $relativeDays = null,
        ?int $capacity = null,
    ): void {
        // 画像パスを取得
        $imagePath = $this->getImagePath($course, $imageFile);
        // 講座を更新
        $course->update([
            'title' => $title,
            'image' => $imagePath,
            'status' => $status,
            'deadline_type' => $deadlineType->value,
            'capacity' => $capacity,
        ]);

        if (! CourseDeadline::hasDeadline($deadlineType)) {
            // 受講期限設定がない場合、講座期限と受講生の期限を削除
            $course->courseDeadline()->delete();
            $course->attendances()->update(['attendance_deadline' => null]);

            return;
        }

        // 受講期限テーブルを更新
        $course->courseDeadline()->updateOrCreate(
            ['course_id' => $course->id],
            [
                'fixed_date' => $deadlineType === DeadlineTypeEnum::FIXED_DATE ? $fixedDate : null,
                'relative_days' => $deadlineType === DeadlineTypeEnum::RELATIVE_DAYS ? $relativeDays : null,
            ]
        );

        // 受講期限タイプに応じて処理を分岐
        match ($deadlineType) {
            DeadlineTypeEnum::FIXED_DATE => $this->updateFixedDeadline($course, $fixedDate),
            DeadlineTypeEnum::RELATIVE_DAYS => $this->updateRelativeDeadline($course, $relativeDays, $calculateDeadline),
            DeadlineTypeEnum::NONE => $course->attendances()->update(['attendance_deadline' => null]),
        };
    }

    /**
     * 受講期限（固定年月日）の更新
     */
    private function updateFixedDeadline(Course $course, ?string $fixedDate): void
    {
        if ($fixedDate === null) {
            // 固定日がnullの場合は例外をスロー
            throw new InvalidArgumentException('Fixed date cannot be null.');
        }

        // 受講生の受講期限（固定日）を一括更新
        $course->attendances()->update([
            'attendance_deadline' => $fixedDate,
        ]);
    }

    /**
     * 受講期限（受講日から〇日）の更新
     */
    private function updateRelativeDeadline(
        Course $course,
        ?int $relativeDays,
        CalculateDeadlineService $calculateDeadline,
    ): void {
        if ($relativeDays === null) {
            // nullの場合は例外をスロー
            throw new InvalidArgumentException('Relative days must not be null.');
        }

        // 受講期限を計算して更新
        $attendances = $course->attendances()->get();
        $attendances->each(function (Attendance $attendance) use ($relativeDays, $calculateDeadline) {
            // 新しい受講期限を計算
            $newDeadline = $calculateDeadline(
                deadlineType: DeadlineTypeEnum::RELATIVE_DAYS->value,
                fixedDate: null,
                relativeDays: $relativeDays,
                startAt: $attendance->created_at->toImmutable(),
            );

            // 新しい受講期限が null の場合は例外をスロー
            if ($newDeadline === null) {
                throw new RuntimeException('Failed to calculate new attendance deadline.');
            }

            // 受講生の受講期限を更新
            $attendance->update([
                'attendance_deadline' => $newDeadline->format('Y-m-d'),
            ]);
        });
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
