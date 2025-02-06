<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講座詳細取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/course/1');

        // assert
        $response->assertStatus(200);
    }

    public function test_権限のない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');
        Course::find(5)->delete();

        // act
        $response = $this->getJson('/api/v1/instructor/course/2');

        // assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/course/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
        ]);
    }
}
