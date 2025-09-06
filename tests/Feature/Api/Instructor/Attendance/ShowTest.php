<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_マネージャーで受講状況を取得(): void
    {
        // arrange
        $manager = Instructor::find(1);
        $this->actingAs($manager, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/attendance/1');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'students_count',
            ],
        ]);
    }

    public function test_マネージャーで配下の講師で受講状況を取得(): void
    {
        // arrange
        $manager = Instructor::find(1);
        $this->actingAs($manager, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/attendance/1');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'students_count',
            ],
        ]);
    }

    public function test_講師で状況状況を取得(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/attendance/4');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'students_count',
            ],
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(3);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/attendance/4');

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
        $response = $this->getJson('/api/v1/instructor/attendance/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }
}
