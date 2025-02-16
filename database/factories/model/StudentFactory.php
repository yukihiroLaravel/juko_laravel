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
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'nick_name' => $this->faker->userName(), 
            'occupation' => $this->faker->jobTitle(),
            'birth_date' => $this->faker->date(),
            'gender' => $this->faker->randomElement([0, 1]), 
            'address' => $this->faker->address(),
        ];
    }
}