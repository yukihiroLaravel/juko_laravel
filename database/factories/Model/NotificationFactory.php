<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = \App\Model\Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'course_id' => 1,
            'instructor_id' => 1,
            'title' => fake()->sentence,
            'type' => 'once',
            'start_date' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'end_date' => fake()->dateTimeBetween('+1 week', '+2 week'),
            'content' => fake()->paragraph,
        ];
    }
}
