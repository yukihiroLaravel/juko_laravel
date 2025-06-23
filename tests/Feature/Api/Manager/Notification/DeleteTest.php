<?php

namespace Tests\Feature\Api\Manager\Notification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Model\Instructor;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/notification/2');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        // リレーションされているデータが削除されているか確認
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'id' => 1,
        ]);
    }

    public function test_権限がないマネージャー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/notification/2');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid instructor_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/notification/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
        ]);
    }
}
