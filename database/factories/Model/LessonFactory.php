<?php

namespace Database\Factories\Model;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Chapter;
use App\Model\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title' => fake()->text(50),
            'url' => fake()->url(),
            'remarks' => fake()->sentence(),
            'status' => LessonStatusEnum::PUBLIC->value,
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
