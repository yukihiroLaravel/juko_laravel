<?php

namespace Tests\Feature\Api\Instructor\Lesson;

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

    public function test_レッスン並び替え_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter/2/lesson/sort', [
            'lessons' => [
                ['lesson_id' => 5, 'order' => 1],
                ['lesson_id' => 4, 'order' => 2],
                ['lesson_id' => 3, 'order' => 3],
                ['lesson_id' => 2, 'order' => 4],
            ],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        collect([5, 4, 3, 2])->each(function ($id, $index) {
            $this->assertDatabaseHas('lessons', [
                'id' => $id,
                'chapter_id' => 2,
                'order' => $index + 1,
            ]);
        });
    }

    public function test_権限がない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/1/chapter/2/lesson/sort', [
            'lessons' => [
                ['lesson_id' => 5, 'order' => 1],
                ['lesson_id' => 4, 'order' => 2],
                ['lesson_id' => 3, 'order' => 3],
                ['lesson_id' => 2, 'order' => 4],
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
        $response = $this->postJson('/api/v1/instructor/course/aaa/chapter/bbb/lesson/sort');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'lessons',
        ]);
    }
}
