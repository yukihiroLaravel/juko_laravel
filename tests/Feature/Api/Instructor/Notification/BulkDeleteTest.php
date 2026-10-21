<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-009 */
    #[DataProvider('instructorTypeProvider')]
    public function test_選んだお知らせをまとめて削除できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $notification = Notification::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.bulk-delete'), [
            'notifications' => [$notification->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** AC-NOTIF-012 */
    public function test_他の講師のお知らせが混ざっていると1件も削除されない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $ownNotification = Notification::factory()->create(['instructor_id' => $instructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherNotification = Notification::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.bulk-delete'), [
            'notifications' => [$otherNotification->id, $ownNotification->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertNotSoftDeleted('notifications', ['id' => $ownNotification->id]);
        $this->assertNotSoftDeleted('notifications', ['id' => $otherNotification->id]);
    }

    public function test_削除するお知らせを1件も選ばないと削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.notifications.bulk-delete'), [
            'notifications' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notifications',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instructorTypeProvider(): array
    {
        return [
            'マネージャー' => ['manager'],
            '講師' => ['instructor'],
        ];
    }
}
