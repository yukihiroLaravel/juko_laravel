<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_チャプター並び替え_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter/sort', [
            'chapters' => [
                ['chapter_id' => 1, 'order' => 3],
                ['chapter_id' => 2, 'order' => 2],
                ['chapter_id' => 3, 'order' => 1],
            ],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        collect([3, 2, 1])->each(function ($id, $index) {
            $this->assertDatabaseHas('chapters', [
                'id' => $id,
                'course_id' => 1,
                'order' => $index + 1,
            ]);
        });
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter/sort', [
            'chapters' => [
                ['chapter_id' => 1, 'order' => 3],
                ['chapter_id' => 2, 'order' => 2],
                ['chapter_id' => 3, 'order' => 1],
            ],
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
        $response = $this->postJson('/api/v1/instructor/course/aaa/chapter/sort', []);
        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapters',
        ]);
    }
}
