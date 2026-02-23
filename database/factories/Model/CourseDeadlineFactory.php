<?php

namespace Database\Factories\Model;

use App\Model\Course;
use App\Model\CourseDeadline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\CourseDeadline>
 */
class CourseDeadlineFactory extends Factory
{
    protected $model = CourseDeadline::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'fixed_date' => null,
            'relative_days' => null,
        ];
    }
}
