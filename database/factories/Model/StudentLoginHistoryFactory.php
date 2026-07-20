<?php

namespace Database\Factories\Model;

use App\Model;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class StudentLoginHistoryFactory extends Factory
{
    protected $model = StudentLoginHistory::class;

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
            'logged_in_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
