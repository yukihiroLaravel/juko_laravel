<?php

namespace Tests\Feature\Api\Instructor\Chapter;

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

    public function test_チャプター登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter', [
            'title' => 'title',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
            'chapter_id',
        ]);
        $this->assertDatabaseHas('chapters', [
            'course_id' => 1,
            'title' => 'title',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter', [
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
        $response = $this->postJson('/api/v1/instructor/course/aaa/chapter', [
            'title' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
        ]);
    }
}
