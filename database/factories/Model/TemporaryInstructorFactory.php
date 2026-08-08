<?php

namespace Database\Factories\Model;

use App\Model\Instructor;
use App\Model\TemporaryInstructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemporaryInstructor>
 */
class TemporaryInstructorFactory extends Factory
{
    protected $model = TemporaryInstructor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'manager_id' => Instructor::factory(),
            'trial_count' => 0,
            'code' => fake()->numerify('####'),
            'token' => fake()->regexify('[A-Za-z0-9]{10}'),
            'expire_at' => now()->addHour(),
            'nick_name' => fake()->userName(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'type' => 'instructor',
        ];
    }
}
