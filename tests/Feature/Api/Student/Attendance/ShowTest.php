<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Student;
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

    public function test_受講取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/1');

        // assert
        $response->assertStatus(200);
    }

    public function test_権限がない受講生_失敗(): void
    {
        // arrange
        $student = Student::find(2);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/1');

        // assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }
}
