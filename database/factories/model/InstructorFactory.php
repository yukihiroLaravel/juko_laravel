<?php

namespace Database\Factories\Model;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition()
    {
        return [
            'nick_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName, 
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'type' => 'manager',
        ];
    }
}