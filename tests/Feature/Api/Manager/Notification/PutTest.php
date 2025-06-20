<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

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

        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        // act
        $response = $this->putJson('/api/v1/manager/notification/'.$notification->id, [
            'title' => 'update',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'updateテスト',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'title' => 'update',
            'type' => TypeEnum::ONCE,
            'content' => 'updateテスト',
        ]);
    }

    public function test_お知らせ更新_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        // act
        $response = $this->putJson('/api/v1/manager/notification/'.$notification->id, [
            'title' => '', // 空
            'type' => '',  // 空
            'start_date' => 'invalid-date', // 無効な日付
            'end_date' => '2025-01-01 18:00:00', // 有効な日付
            'content' => '', // 空
            'status' => 'aaaa', // 有効なステータス
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }

    public function test_お知らせ更新_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        $notification = Notification::factory()->create([
            'instructor_id' => 1,
        ]);

        // act
        $response = $this->putJson('/api/v1/manager/notification/'.$notification->id, [
            'title' => '権限なしテスト',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-10 18:00:00',
            'content' => 'これはテストの内容です。',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
