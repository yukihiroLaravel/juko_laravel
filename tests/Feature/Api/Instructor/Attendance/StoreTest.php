<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_受講登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/attendance', [
            'course_id' => 1,
            'student_id' => 3,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
    }

    public function test_受講中の生徒_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/attendance', [
            'course_id' => 1,
            'student_id' => 1,
        ]);

        // assert
        $response->assertStatus(403);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/attendance', [
            'course_id' => 2,
            'student_id' => 3,
        ]);

        // assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/attendance', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'student_id',
        ]);
    }
}
