<?php

namespace Database\Factories\Model;

use App\Model\TemporaryStudent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\TemporaryStudent>
 */
class TemporaryStudentFactory extends Factory
{
    protected $model = TemporaryStudent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'trial_count' => 0,
            'code' => fake()->numerify('####'),
            'token' => fake()->regexify('[A-Za-z0-9]{10}'),
            'expire_at' => now()->addHour(),
            'nick_name' => fake()->userName(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'occupation' => fake()->jobTitle(),
            'purpose' => fake()->sentence(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(['man', 'woman', 'unknown']),
        ];
    }
}
