<?php

namespace Database\Factories\Model;

use App\Model\Notification;
use App\Model\Student;
use App\Model\ViewedOnceNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ViewedOnceNotification>
 */
class ViewedOnceNotificationFactory extends Factory
{
    protected $model = ViewedOnceNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'student_id' => Student::factory(),
        ];
    }
}
