<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function statusChanges(): array
    {
        return [
            '非公開のお知らせを公開にする' => ['private', 'public'],
            '公開のお知らせを非公開にする' => ['public', 'private'],
        ];
    }

    /** AC-NOTIF-010 */
    #[DataProvider('statusChanges')]
    public function test_自分のすべてのお知らせの公開状態をまとめて変更できる(string $before, string $after): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => $before,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => $after,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => $after,
        ]);
    }

    public function test_公開状態が空のときはまとめて変更できない(): void
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

    public function test_公開でも非公開でもない公開状態ではまとめて変更できない(): void
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

    public function test_ログインしていないときはまとめて変更できない(): void
    {
        // Act
        $response = $this->putJson(route('instructor.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
