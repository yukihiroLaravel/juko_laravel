<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座ステータス一括更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'public',
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => 'public',
        ]);
    }

    public function test_権限がない講師が講座ステータス一括更新_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'public',
        ]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_講座idが空配列_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [],
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_存在しない講座id_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [99999],
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_ステータスがdraft_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [1],
            'status' => 'draft',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
