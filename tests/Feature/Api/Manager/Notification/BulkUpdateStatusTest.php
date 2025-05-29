<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Enums\Notification\StatusEnum;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせステータス一括更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/notification/status/', [
            'notifications' => [1, 4],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 4,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    public function test_お知らせ更新_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/notification/status/', [
            'notifications' => [],
            'status' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notifications' => '対象のお知らせを1件以上選択してください。',
            'status' => 'ステータスは必須です。',
        ]);

    }

    public function test_お知らせ更新_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/notification/status/', [
            'notifications' => [1, 4],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
