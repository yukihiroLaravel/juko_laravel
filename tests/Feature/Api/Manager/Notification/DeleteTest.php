<?php

namespace Tests\Feature\Api\Manager\Notification;

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
        $manager = Instructor::factory()->create();
        $notification = Notification::factory()->create(['instructor_id' => $manager->id]);
        $viewedNotification = ViewedOnceNotification::factory()->create([
            'notification_id' => $notification->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'id' => $viewedNotification->id,
        ]);
    }

    /** AC-AUTHZ-004 */
    public function test_ほかのマネージャーのお知らせは削除できない(): void
    {
        // Arrange
        $ownerManager = Instructor::factory()->create();
        $notification = Notification::factory()->create(['instructor_id' => $ownerManager->id]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertNotSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_マネージャーでない講師はお知らせを削除できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.notifications.delete', ['notification_id' => 1]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_お知らせの指定が整数でないときは入力エラーになる(): void
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
