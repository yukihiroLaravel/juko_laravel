<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteAllLessonsTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_指定したチャプターのレッスンを全て完了_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->putJson('/api/v1/attendance/1/chapter/2/complete');

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => 2,
            'lesson_id' => 2,
            'status' => 'completed_attendance',
        ]);
    }

    public function test_権限がない生徒_失敗(): void
    {
        // arrange
        $student = Student::find(2);
        $this->actingAs($student);

        // act
        $response = $this->putJson('/api/v1/attendance/1/chapter/2/complete');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_権限がないチャプター_失敗(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->putJson('/api/v1/attendance/1/chapter/4/complete');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid chapter.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->putJson('/api/v1/attendance/aaa/chapter/bbb/complete');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
            'chapter_id',
        ]);
    }
}
