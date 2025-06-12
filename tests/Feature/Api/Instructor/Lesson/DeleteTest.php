<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_レッスン削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/5/chapter/6/lesson/10');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        // 論理削除されているか確認
        $this->assertSoftDeleted('lessons', [
            'id' => 10,
            'order' => 0,
        ]);
    }

    public function test_受講済みレッスン削除_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/1/chapter/1/lesson/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this lesson has attendance.',
        ]);

    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/5/chapter/6/lesson/10');

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
        $response = $this->deleteJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/ccc', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'lesson_id',
        ]);
    }
}
