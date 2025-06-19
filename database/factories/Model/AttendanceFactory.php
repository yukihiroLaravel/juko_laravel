<?php

namespace Database\Factories\Model;

use App\Model\Course;
use App\Model\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = \App\Model\Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
        ];
    }
}
