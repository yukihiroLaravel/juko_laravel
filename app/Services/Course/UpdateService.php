<?php
declare(strict_types=1);

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Services\Attendance\CalculateDeadlineService;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
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
        ?string $fixedDate = null,
        ?int $relativeDays = null,
        CalculateDeadlineService $calculateDeadline,
    ): void {
            // 画像パスを取得
            $imagePath = $this->getImagePath($course, $imageFile);
            // 講座を更新
            $course->update([
                'title' => $title,
                'image' => $imagePath,
                'status' => $status,
                'deadline_type' => $deadlineType->value,
            ]);

            // もし受講期限設定がない場合、講座期限と受講生の期限を削除
            if (! $course->courseDeadline?->hasDeadline($deadlineType)) {
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
                // deadline が NONE の場合は受講生の期限を null に更新
                DeadlineTypeEnum::NONE => $course->attendances()->update(['attendance_deadline' => null]),
                // 想定外の受講期限タイプの場合は例外をスロー
                default => throw new LogicException("Unexpected deadline type: {$deadlineType->value}"),
            };
    }
    /**
     * 受講期限（固定年月日）の更新
     */
    private function updateFixedDeadline(Course $course, ?string $fixedDate): void
    {
        // 固定年月日が null の場合は「nullではダメ」とスロー
        if ($fixedDate === null) {
            throw new InvalidArgumentException('Fixed date cannot be null.');
        }
        // 受講生の受講期限（固定日）を一括更新
        $attendanceIds = $course->attendances()->update([
            'attendance_deadline' => $fixedDate
        ]);
    }

    /**
     * 受講期限（受講日から〇日）の更新
     */
    private function updateRelativeDeadline(
        Course $course,
        ?int $relativeDays,
        CalculateDeadlineService $calculateDeadline,
    ): void{
        // 受講日からの日数がnullの場合は「nullではダメ」とスロー
        if ($relativeDays === null) {
            throw new InvalidArgumentException('Relative days must not be null.');
        }
        
        // 受講期限を計算して更新
        $attendances = $course->attendances()->get();
        foreach ($attendances as $attendance) {
            // 受講開始日を取得
            $startAt = new DateTimeImmutable($attendance->created_at);
            // 新しい受講期限を計算
            $newDeadline = $calculateDeadline(
                DeadlineTypeEnum::RELATIVE_DAYS->value,
                null,
                $relativeDays,
                $startAt
            );

            // 新しい受講期限が null の場合は例外をスロー
            if ($newDeadline === null) {
                throw new RuntimeException('Failed to calculate new attendance deadline.');
            }

            // 受講生の受講期限を更新
            $attendance->update([
                'attendance_deadline' => $newDeadline?->format('Y-m-d'),
            ]);
        }
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
