<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeAllTest extends TestCase
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
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $notificationType = 'once';
        $response = $this->putJson('/api/v1/manager/notification/type/all', [
            'notification_type' => $notificationType,
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'type' => 'once',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $notificationType = 'action';
        $response = $this->putJson('/api/v1/manager/notification/type/all', [
            'notification_type' => $notificationType,
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected notification type is invalid.',
        ]);
    }

    public function test_お知らせ登録_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        // act
        $notificationType = 'once';
        $response = $this->putJson('/api/v1/manager/notification/type/all', [
            'notification_type' => $notificationType,
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
