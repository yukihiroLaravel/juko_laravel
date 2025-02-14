<?php

namespace Tests\Feature;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/notification/index');

        // assert
        $response->assertStatus(200);
    }

    public function test_お知らせ登録_権限エラー(): void
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
