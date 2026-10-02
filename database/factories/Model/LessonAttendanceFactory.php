<?php

namespace Database\Factories\Model;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonAttendance>
 */
class LessonAttendanceFactory extends Factory
{
    protected $model = LessonAttendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'attendance_id' => Attendance::factory(),
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ];
    }

    /**
     * 受講済みの状態
     *
     * 完了日時が記録されているかどうかが受講済みの判定条件のため、表示用ステータスと合わせて記録する
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }
}
