<?php

namespace Database\Factories;

use App\Model\StudentLoginHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model>
 */
class StudentLoginHistoryFactory extends Factory
{
    protected $model = StudentLoginHistory::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Model\Student::factory(),
            'logged_in_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
