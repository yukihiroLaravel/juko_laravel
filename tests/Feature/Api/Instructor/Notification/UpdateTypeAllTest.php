<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'type' => 'always',
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.update-type-all'), [
            'notification_type' => 'once',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'once',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.update-type-all'), [
            'notification_type' => 'action',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected notification type is invalid.',
        ]);
    }
}
