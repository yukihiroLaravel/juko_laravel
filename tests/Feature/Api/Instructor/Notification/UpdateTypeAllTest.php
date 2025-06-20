<?php

namespace Tests\Feature\Api\Instructor\Notification;

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
        $response = $this->putJson("/api/v1/instructor/notification/type/{$notificationType}/all");

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
        $response = $this->putJson("/api/v1/instructor/notification/type/{$notificationType}/all");

        // assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected notification type is invalid.',
        ]);
    }
}
