<?php

namespace Tests\Feature\Api\Manager\Attendance;

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

    public function test_受講登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/attendance', [
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
        $response = $this->postJson('/api/v1/manager/attendance', [
            'course_id' => 1,
            'student_id' => 1,
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Attendance record already exists.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/attendance', [
            'course_id' => 2,
            'student_id' => 3,
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
        $response = $this->postJson('/api/v1/manager/attendance', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'student_id',
        ]);
    }
}
