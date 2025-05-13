<?php

namespace Database\Factories\Model;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'status' => Course::STATUS_PUBLIC,
            'instructor_id' => Instructor::factory(),
            'image' => 'course/default.png',
        ];
    }
}
