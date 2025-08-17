<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.notifications');
    }

    public function test_権限エラー_失敗(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/index');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
