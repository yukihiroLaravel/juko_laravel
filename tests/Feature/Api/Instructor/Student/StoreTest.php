<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_生徒取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('students', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'given_name_by_instructor',
            'email',
        ]);
    }
}
