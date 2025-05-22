<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_受講生一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/student/index');

        // assert
        $response->assertStatus(200);
    }

    public function test_講座id指定_要件定義されている講座指定_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');
        Course::find(5)->delete();

        // act
        $response = $this->getJson('/api/v1/instructor/student/index?courses[]=5');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'courses.0',
        ]);
    }

    public function test_講座id指定_講師が一致しない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/student/index?courses[]=2');

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
        $response = $this->getJson('/api/v1/instructor/student/index?courses[]=aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'courses.0',
        ]);
    }
}
