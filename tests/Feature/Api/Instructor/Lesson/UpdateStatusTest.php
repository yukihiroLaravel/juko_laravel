<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_レッスン状態更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/2/lesson/2/status', [
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
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/2/lesson/2/status', [
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
        $response = $this->patchJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/ccc/status', [
            'status' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The status field is required.',
            'course_id' => 'The course id must be an integer.',
            'chapter_id' => 'The chapter id must be an integer.',
            'lesson_id' => 'The lesson id must be an integer.',
        ]);
    }
}
