<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use App\Model\ViewedOnceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-013 */
    public function test_お知らせを削除すると受講生の確認済みの記録も削除される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $notification = Notification::factory()->create(['instructor_id' => $instructor->id]);
        $viewedOnce = ViewedOnceNotification::factory()->create(['notification_id' => $notification->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'id' => $viewedOnce->id,
        ]);
    }

    /** AC-AUTHZ-004 */
    public function test_他の講師のお知らせは削除できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $notification = Notification::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_お知らせの指定が不正なときは削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.delete', ['notification_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
        ]);
    }
}
