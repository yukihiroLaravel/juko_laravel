<?php

namespace Tests\Feature\Api\Manager\Student;

use App\Model\Instructor;
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

    public function test_生徒取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/student/2');

        // assert
        $response->assertStatus(200);
    }

    public function test_配下ではない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/student/2');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid student_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/student/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id',
        ]);
    }
}
