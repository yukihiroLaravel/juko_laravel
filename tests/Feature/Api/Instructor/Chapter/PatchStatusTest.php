<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatchStatusTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_チャプターのステータス変更_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/status', [
            'chapters' => [1, 2],
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => 1,
            'status' => 'private',
        ]);
    }

    public function test_講師が一致しない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/1/chapter/status', [
            'chapters' => [1, 2],
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座が一致しない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/2/chapter/status', [
            'chapters' => [1, 2],
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid course_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/aaa/chapter/status', [
            'chapters' => 'string',
            'status' => 'string',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapters',
            'status',
        ]);
    }
}
