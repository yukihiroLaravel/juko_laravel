<?php

namespace Tests\Feature\Api\Student;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    // setup
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
}
