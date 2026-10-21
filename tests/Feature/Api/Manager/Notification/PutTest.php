<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-008 */
    public function test_お知らせの内容を更新できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $notification = Notification::factory()->create([
            'instructor_id' => $manager->id,
            'type' => TypeEnum::ALWAYS,
            'status' => 'private',
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put', ['notification_id' => $notification->id]), [
            'title' => 'update',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'updateテスト',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'update',
            'type' => TypeEnum::ONCE,
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'updateテスト',
            'status' => 'public',
        ]);
    }

    public function test_お知らせの項目が不正なときは入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $notification = Notification::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put', ['notification_id' => $notification->id]), [
            'title' => '',
            'type' => '',
            'start_date' => 'invalid-date',
            'end_date' => '2025-01-01 18:00:00',
            'content' => '',
            'status' => 'aaaa',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }

    public function test_マネージャーでない講師はお知らせを更新できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put', ['notification_id' => 1]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
