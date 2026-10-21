<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講期限なし_講座更新_成功(): void
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

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
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

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
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

    /** AC-COMMON-025 */
    public function test_画像を送ると差し替え前の画像が消えて新しい画像が保存される(): void
    {
        // Arrange
        Storage::fake();
        Storage::fake('public');
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'image' => 'course/old.jpg',
        ]);
        Storage::disk('public')->put('course/old.jpg', 'old');
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => UploadedFile::fake()->image('new.jpg'),
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing('course/old.jpg');
        $newImage = $course->fresh()->getRawOriginal('image');
        $this->assertNotSame('course/old.jpg', $newImage);
        Storage::assertExists('public/'.$newImage);
    }

    /** AC-AUTHZ-001 */
    public function test_権限がない_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_不正な値を送ると更新できない(): void
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

    public function test_定員なしにして講座更新_成功(): void
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

    /** AC-AUTHOR-011 */
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

    /** AC-AUTHOR-011 */
    public function test_定員が受講者数を下回ると更新できない(): void
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
