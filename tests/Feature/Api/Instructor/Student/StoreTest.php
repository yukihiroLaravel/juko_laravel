<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_生徒登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('students', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);
        $this->assertDatabaseHas('attendances', [
            'course_id' => $course->id,
        ]);
    }

    public function test_定員無制限の講座に生徒登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => null,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'Jane',
            'email' => 'jane@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('students', [
            'email' => 'jane@example.com',
        ]);
        $this->assertDatabaseHas('attendances', [
            'course_id' => $course->id,
        ]);
    }

    public function test_定員超過_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 1,
        ]);
        // 既に1人登録済み（定員1に到達）
        Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'Over',
            'email' => 'over@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座に生徒を登録しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'Unauthorized',
            'email' => 'unauthorized@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_論理削除済み講座_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.student.store'), [
            'given_name_by_instructor' => 'Deleted',
            'email' => 'deleted@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
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
            'course_id',
        ]);
    }
}
