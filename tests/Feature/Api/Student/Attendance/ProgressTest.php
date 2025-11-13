<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_受講進捗を取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/1/progress');

        // assert
        $response->assertStatus(200);
    }

    public function test_受講進捗を取得_他の生徒の進捗を取得_失敗(): void
    {
        // arrange
        $student = Student::find(2);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/1/progress');

        // assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/abc/progress');

        // assert
        $response->assertStatus(422);
    }

    public function test_受講期限切れの受講進捗を取得_失敗(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);
        Attendance::find(1)->update(['attendance_deadline' => now()->subDays(1)]);

        // act
        $response = $this->getJson('/api/v1/attendance/1/progress');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講期限当日は受講進捗を取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);
        Attendance::find(1)->update(['attendance_deadline' => now()]);

        // act
        $response = $this->getJson('/api/v1/attendance/1/progress');

        // assert
        $response->assertStatus(200);
    }
}
