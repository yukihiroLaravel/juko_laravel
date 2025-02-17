<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteAllCoursesTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_すべての講座を完了_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->putJson('/api/v1/attendance/course/complete');

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => 1,
            'status' => 'completed_attendance',
        ]);
    }
}
