<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use App\Model\ViewedOnceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ削除_成功(): void
    {
        // Arrange — ViewedOnceNotificationも作成してカスケード削除を確認
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $student = Student::factory()->create();
        $viewedOnce = ViewedOnceNotification::factory()->create([
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notification.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'id' => $viewedOnce->id,
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
        $response = $this->deleteJson(route('instructor.notification.delete', ['notification_id' => $notification->id]));

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
        $response = $this->deleteJson(route('instructor.notification.delete', ['notification_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
        ]);
    }
}
