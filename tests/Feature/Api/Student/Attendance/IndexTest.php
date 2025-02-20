<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_受講一覧を取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/index');

        // assert
        $response->assertStatus(200);
    }
}
