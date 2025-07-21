<?php

namespace Tests\Feature\Api\Manager\Student;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
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
        $response = $this->postJson('/api/v1/manager/student', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('students', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function test_権限エラー(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/student', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/student', []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'given_name_by_instructor',
            'email',
        ]);
    }
}
