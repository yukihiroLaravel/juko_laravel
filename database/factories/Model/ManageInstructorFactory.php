<?php

namespace Database\Factories\Model;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\ManageInstructor>
 */
class ManageInstructorFactory extends Factory
{
    protected $model = ManageInstructor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'instructor_id' => Instructor::factory(),
            'manager_id' => Instructor::factory(),
        ];
    }
}
