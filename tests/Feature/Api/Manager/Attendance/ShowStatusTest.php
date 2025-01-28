<?php

namespace Tests\Feature\Api\Manager\Attendance;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_当日の出席状況を取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/1/attendance/status/today');

        // assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/1/attendance/status/month');

        // assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/1000/attendance/status/invalid');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    public function test_配下ではない講師の講座_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/4/attendance/status/today');

        // assert
        $response->assertStatus(403);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/2/attendance/status/today');

        // assert
        $response->assertStatus(403);
    }
}
