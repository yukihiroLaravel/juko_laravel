<?php

namespace Tests\Feature\Api\Student\LessonAttendance;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatchStatusTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_レッスン受講状態を更新_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->patchJson('/api/v1/lesson_attendance/1', [
            'status' => 'before_attendance',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => 1,
            'status' => 'before_attendance',
        ]);
    }

    public function test_レッスン受講状態を更新_他の生徒の受講状態を更新_失敗(): void
    {
        // arrange
        $student = Student::find(2);
        $this->actingAs($student);

        // act
        $response = $this->patchJson('/api/v1/lesson_attendance/1', [
            'status' => 'before_attendance',
        ]);

        // assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->patchJson('/api/v1/lesson_attendance/abc', [
            'status' => 'aaa',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lesson_attendance_id',
            'status',
        ]);
    }
}
