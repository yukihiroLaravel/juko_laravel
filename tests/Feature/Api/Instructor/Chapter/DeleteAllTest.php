<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAllTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_チャプター全削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/5/chapter/all');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        // 論理削除されているか確認
        $this->assertSoftDeleted('chapters', [
            'course_id' => 5,
        ]);
        $this->assertSoftDeleted('lessons', [
            'chapter_id' => 6,
        ]);
    }

    public function test_受講済みレッスン削除_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/1/chapter/all');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This lesson has attendance.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/2/chapter/all');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid instructor_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/aaa/chapter/all');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
        ]);
    }
}
