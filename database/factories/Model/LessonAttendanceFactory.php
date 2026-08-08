<?php

namespace Database\Factories\Model;

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
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ];
    }

    /**
     * 完了済みの状態
     *
     * 完了判定は完了日時で行うため、ステータスと合わせて完了日時も記録する。
     * 完了日時が結果に影響するテストでは、明示的に日時を指定すること。
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }
}
