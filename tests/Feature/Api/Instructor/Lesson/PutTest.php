<?php

namespace Tests\Feature\Api\Instructor\Lesson;

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

    public function test_レッスン更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/course/1/chapter/1/lesson/1', [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => 1,
            'chapter_id' => 1,
            'title' => 'title',
        ]);
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/course/2/chapter/4/lesson/7', [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
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
        $response = $this->putJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/ccc', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'lesson_id',
            'title',
            'url',
            'status',
        ]);
    }
}
