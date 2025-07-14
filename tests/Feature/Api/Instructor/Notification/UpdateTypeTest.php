<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeTest extends TestCase
{
    // データベース初期化
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $notificationType = 'once';
        $response = $this->putJson('/api/v1/instructor/notification/type', [
            'notification_type' => $notificationType,
            'notifications' => [2],
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'type' => 'once',
        ]);
    }

    public function test_権限なし_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $notificationType = 'once';
        $response = $this->putJson('/api/v1/instructor/notification/type', [
            'notification_type' => $notificationType,
            'notifications' => [1],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid instructor_id.',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $notificationType = 'action';
        $response = $this->putJson('/api/v1/instructor/notification/type', [
            'notification_type' => $notificationType,
            'notifications' => [],
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_type' => 'The selected notification type is invalid.',
            'notifications' => 'The notifications field is required.',
        ]);
    }
}
