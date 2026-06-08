<?php

namespace Tests\Feature\Api\Manager\Notification;

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
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
        ]);
        $student = Student::factory()->create();
        $viewedNotification = ViewedOnceNotification::factory()->create([
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);

        // リレーションされているデータが削除されているか確認
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'id' => $viewedNotification->id,
        ]);
    }

    public function test_権限がないマネージャー_失敗(): void
    {
        // Arrange — 別のマネージャーの通知を削除しようとする
        $ownerManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerManager->id]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $ownerManager->id,
        ]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャー権限がない_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $notification = Notification::factory()->create();
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
        ]);
    }
}
