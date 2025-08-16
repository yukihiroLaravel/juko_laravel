<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
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
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status', [
            'notifications' => [
                2,
            ],
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'status' => 'private',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status', [
            'notifications' => [
                1,
            ],
            'status' => 'private',
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
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/aaaa', [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
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
        ]);
    }
}
