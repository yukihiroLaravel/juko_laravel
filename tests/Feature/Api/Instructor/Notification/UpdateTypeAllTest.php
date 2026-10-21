<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeAllTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-010 */
    public function test_自分のすべてのお知らせの種類をまとめて変更できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
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

    public function test_種類が不正なときはまとめて変更できない(): void
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
