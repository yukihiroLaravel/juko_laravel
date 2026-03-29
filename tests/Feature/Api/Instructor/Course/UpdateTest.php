<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test__受講期限なし_講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
    }

    public function test_固定受講期限あり_講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'public',
            'deadline_type' => 'fixed_date',
            'fixed_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'public',
            'deadline_type' => 'fixed_date',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => now()->addDays(30)->format('Y-m-d 00:00:00'),
            'relative_days' => null,
        ]);
    }

    public function test_相対受講期限あり_講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'public',
            'deadline_type' => 'relative_days',
            'relative_days' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'public',
            'deadline_type' => 'relative_days',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => null,
            'relative_days' => 30,
        ]);
    }

    public function test_権限がない_失敗(): void
    {
        // Arrange — 別の講師の講座
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => 'aaa']), [
            'title' => '',
            'image' => null,
            'status' => 'invalid_status',
            'deadline_type' => 'invalid_type',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id', 'title', 'image', 'status', 'deadline_type',
        ]);
    }

    public function test_定員を指定して講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
            'capacity' => 20,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => 20,
        ]);
    }

    public function test_定員をnullにして講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
            'capacity' => null,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => null,
        ]);
    }

    public function test_定員が受講者数と同じ_講座更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->count(3)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
            'capacity' => 3,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => 3,
        ]);
    }

    public function test_定員が受講者数を下回る_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->count(5)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
            'capacity' => 3,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }
}
