<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_レッスン登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/2/chapter/4/lesson', [
            'title' => 'title',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
            'lesson_id',
        ]);
        $this->assertDatabaseHas('lessons', [
            'chapter_id' => 4,
            'title' => 'title',
        ]);
    }

    public function test_権限がない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/2/chapter/4/lesson', [
            'title' => 'title',
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
        $response = $this->postJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'title',
        ]);
    }
}
