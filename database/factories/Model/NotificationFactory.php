<?php

namespace Database\Factories\Model;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

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
            'instructor_id' => Instructor::factory(),
            'title' => fake()->sentence,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'end_date' => fake()->dateTimeBetween('+1 week', '+2 week'),
            'content' => fake()->paragraph,
        ];
    }
}
