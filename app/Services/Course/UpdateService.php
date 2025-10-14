<?php

namespace App\Services\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\Attendance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateService
{
    /**
     * 講座登録サービス
     * @param array<int, string|\DateTimeInterface|null> $attendanceDeadlines
     *             受講者ごとの更新後の受講期限（[attendance_id => 'YYYY-MM-DD']）
     */
    public function __invoke(
        Course $course,
        string $title,
        ?UploadedFile $imageFile,
        string $status,
        DeadlineTypeEnum $deadlineType,
        ?string $fixedDate = null,
        ?int $relativeDays = null,
        array $attendanceDeadlines = []
    ): void {
        DB::transaction(function () use (
            $course,
            $title,
            $imageFile,
            $status,
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $attendanceDeadlines
        ) {
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

            $course->courseDeadline()->updateOrCreate(
                ['course_id' => $course->id],
                [
                    'fixed_date' => $deadlineType === DeadlineTypeEnum::FIXED_DATE ? $fixedDate : null,
                    'relative_days' => $deadlineType === DeadlineTypeEnum::RELATIVE_DAYS ? $relativeDays : null,
                ]
            );

            // === Attendance の期限をバルクアップデート ===
            if (! empty($attendanceDeadlines)) {
                // バルクアップデート（MySQL対応）
                $caseSql = '';
                $ids = [];

                foreach ($attendanceDeadlines as $attendanceId => $deadlineDate) {
                    $ids[] = (int) $attendanceId;
                    $date = $deadlineDate ? "'{$deadlineDate}'" : 'NULL';
                    $caseSql .= "WHEN id = {$attendanceId} THEN {$date} ";
                }

                $idsString = implode(',', $ids);
                $query = "
                    UPDATE attendances
                    SET attendance_deadline = CASE {$caseSql}END
                    WHERE id IN ({$idsString})
                ";
                DB::update($query);
            }
        });
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
