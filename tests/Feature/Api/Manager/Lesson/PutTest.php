<?php

namespace Tests\Feature\Api\Manager\Lesson;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_マネージャーのレッスン更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/course/1/chapter/1/lesson/1', [
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

    public function test_配下の講師のレッスン登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/course/2/chapter/4/lesson/7', [
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
            'id' => 7,
            'chapter_id' => 4,
            'title' => 'title',
        ]);
        // ! シーダーに受講データがないので、lesson_attendancesにはデータがない
    }

    public function test_配下でない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/course/2/chapter/4/lesson/7', [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to this lesson.',
        ]);
    }

    public function test_マネージャーではない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/course/2/chapter/4/lesson/7', [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/course/aaa/chapter/bbb/lesson/ccc', []);

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
