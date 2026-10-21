<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeAllTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-011 */
    public function test_お知らせの種類を一括で変更できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $notification1 = Notification::factory()->create([
            'instructor_id' => $manager->id,
            'type' => 'always',
        ]);
        $notification2 = Notification::factory()->create([
            'instructor_id' => $manager->id,
            'type' => 'always',
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.update-type-all'), [
            'notification_type' => 'once',
        ]);

        // Assert
        $response->assertStatus(200);
        foreach ([$notification1, $notification2] as $notification) {
            $this->assertDatabaseHas('notifications', [
                'id' => $notification->id,
                'type' => 'once',
            ]);
        }
    }

    public function test_お知らせの種類に無効な値を指定すると入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.update-type-all'), [
            'notification_type' => 'action',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected notification type is invalid.',
        ]);
    }

    public function test_マネージャーでない講師はお知らせの種類を一括変更できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.update-type-all'));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
