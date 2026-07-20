<?php

namespace Database\Factories\Model;

use App\Model\Attendance;
use App\Model\Lesson;
use App\Model\LessonAttendance;
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
}
