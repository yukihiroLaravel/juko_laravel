<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTitleTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_レッスンタイトル更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/2/lesson/2/title', [
            'title' => '新しいタイトル',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => 2,
            'title' => '新しいタイトル',
        ]);
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/2/lesson/2/title', [
            'title' => '新しいタイトル',
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
        $response = $this->patchJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/ccc/title', [
            'title' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'The title field is required.',
            'course_id' => 'The course id must be an integer.',
            'chapter_id' => 'The chapter id must be an integer.',
            'lesson_id' => 'The lesson id must be an integer.',
        ]);
    }
}
