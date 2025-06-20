<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
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
        $response = $this->putJson('/api/v1/instructor/notification/1', [
            'title' => 'title',
            'type' => 'once',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'title' => 'title',
            'type' => TypeEnum::ONCE,
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/1', [
            'title' => 'title',
            'type' => 'once',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => '2024-01-02 00:00:00',
            'content' => 'content',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/aaaa', [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
            'status' => '',
        ]);

        // assert
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
