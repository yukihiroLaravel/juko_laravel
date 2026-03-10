<?php

namespace Database\Factories\Model;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'nick_name' => fake()->firstName,
            'last_name' => fake()->lastName,
            'first_name' => fake()->firstName,
            'email' => fake()->unique()->safeEmail,
            'password' => bcrypt('password'),
            'type' => 'manager',
        ];
    }
}
