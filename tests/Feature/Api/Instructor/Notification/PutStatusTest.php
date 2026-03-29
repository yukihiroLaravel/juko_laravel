<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
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
        $response = $this->putJson(route('instructor.notifications.put-status'), [
            'notifications' => [
                $notification->id,
            ],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'private',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師のお知らせのステータスを更新しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $ownerInstructor->id,
            'course_id' => $course->id,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status'), [
            'notifications' => [
                $notification->id,
            ],
            'status' => 'private',
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

        // Act — PutTest(個別更新)のバリデーションエラーを検証
        $response = $this->putJson(route('instructor.notifications.put', ['notification_id' => 'aaaa']), [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
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
        ]);
    }
}
