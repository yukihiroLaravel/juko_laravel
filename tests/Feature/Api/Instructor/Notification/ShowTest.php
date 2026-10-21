<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('instructorTypeProvider')]
    public function test_お知らせの内容を取得できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notifications.show', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notification_id', $notification->id);
        $response->assertJsonPath('data.title', $notification->title);
    }

    public function test_お知らせの指定が不正なときは取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notifications.show', ['notification_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
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
