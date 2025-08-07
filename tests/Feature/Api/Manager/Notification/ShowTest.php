<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/1');

        // assert
        $response->assertStatus(200);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'notification_id',
        ]);
    }

    public function test_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/aaa');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
