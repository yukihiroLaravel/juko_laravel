<?php

namespace Tests\Feature\Api\Instructor\Notification;

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
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'type' => 'always',
            'status' => 'private',
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put', ['notification_id' => $notification->id]), [
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

    /** AC-AUTHZ-004 */
    public function test_他の講師のお知らせは更新できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $notification = Notification::factory()->create([
            'instructor_id' => $ownerInstructor->id,
        ]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put', ['notification_id' => $notification->id]), [
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

    public function test_入力がすべて空のときはお知らせを更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put', ['notification_id' => 'aaaa']), [
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
