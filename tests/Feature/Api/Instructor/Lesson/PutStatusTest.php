<?php

namespace Tests\Feature\Api\Instructor\Lesson;

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

    public function test_レッスン更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/course/1/chapter/2/lesson/status', [
            'lessons' => [
                2,
                3,
            ],
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => 2,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => 3,
            'status' => 'private',
        ]);
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/course/1/chapter/2/lesson/status', [
            'lessons' => [
                2,
                3,
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
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/status', [
            'lessons' => [],
            'status' => 'invalid_status', // 無効なステータス
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons',
            'status',
        ]);
    }
}
