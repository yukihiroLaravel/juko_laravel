<?php

namespace Database\Factories\Model;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'order' => fake()->numberBetween(1, 10),
            'title' => fake()->text(50),
            'status' => ChapterStatusEnum::PUBLIC->value,
        ];
    }
}
