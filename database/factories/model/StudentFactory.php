<?php

namespace Database\Factories\Model;

use App\Model\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'nick_name' => fake()->userName(),
            'occupation' => fake()->jobTitle(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement([0, 1]),
            'address' => fake()->address(),
        ];
    }
}
