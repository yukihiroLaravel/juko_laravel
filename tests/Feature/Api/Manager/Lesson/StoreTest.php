<?php

namespace Tests\Feature\Api\Manager\Lesson;

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

    public function test_マネージャーのレッスン登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/1/chapter/1/lesson', [
            'title' => 'title',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
            'lesson_id',
        ]);
        $this->assertDatabaseHas('lessons', [
            'chapter_id' => 1,
            'title' => 'title',
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'lesson_id' => $response['lesson_id'],
        ]);
    }

    public function test_配下の講師のレッスン登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/2/chapter/4/lesson', [
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
        // ! シーダーに受講データがないので、lesson_attendancesにはデータがない
    }

    public function test_配下でない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/2/chapter/4/lesson', [
            'title' => 'title',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーではない講師のレッスン登録_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/2/chapter/4/lesson', [
            'title' => 'title',
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
        $response = $this->postJson('/api/v1/manager/course/aaa/chapter/bbb/lesson', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'title',
        ]);
    }
}
