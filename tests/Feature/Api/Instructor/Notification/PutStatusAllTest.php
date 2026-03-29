<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ一括更新_public_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'private',
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'public',
        ]);
    }

    public function test_お知らせ一括更新_private_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'public',
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'private',
        ]);
    }

    public function test_ステータス空欄_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The status field is required.',
        ]);
    }

    public function test_無効なステータス_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The selected status is invalid.',
        ]);
    }

    public function test_認証なし_失敗(): void
    {
        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
