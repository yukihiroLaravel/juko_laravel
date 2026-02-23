<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
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
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notification.put', ['notification_id' => $notification->id]), [
            'title' => 'title',
            'type' => 'once',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'title',
            'type' => TypeEnum::ONCE,
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $ownerInstructor->id,
            'course_id' => $course->id,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notification.put', ['notification_id' => $notification->id]), [
            'title' => 'title',
            'type' => 'once',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notification.put', ['notification_id' => 'aaaa']), [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }
}
