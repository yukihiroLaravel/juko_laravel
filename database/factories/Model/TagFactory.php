<?php

namespace Database\Factories\Model;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

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
            'content' => fake()->word(),
        ];
    }
}
