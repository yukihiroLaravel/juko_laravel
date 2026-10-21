<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearAllCapacityTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ENROLL-021 */
    public function test_自分の全講座の定員をなくせること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $courses = Course::factory()->count(3)->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 3,
        ]);

        $courses->each(function (Course $course) {
            $this->assertDatabaseHas('courses', [
                'id' => $course->id,
                'capacity' => null,
            ]);
        });
    }

    public function test_定員が設定されていない講座も含めて正常に処理されること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => null,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
    }

    public function test_講座がない場合も正常にレスポンスされること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 0,
        ]);
    }

    /** AC-ENROLL-020 */
    public function test_受講者が存在する講座が含まれている場合はエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        Attendance::factory()->create(['course_id' => $course->id]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => 10,
        ]);
    }

    /** AC-ENROLL-020 */
    public function test_一部に受講者が存在する講座を含む場合はエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $courseWithoutStudents = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $courseWithStudents = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        Attendance::factory()->create(['course_id' => $courseWithStudents->id]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);

        $this->assertDatabaseHas('courses', [
            'id' => $courseWithoutStudents->id,
            'capacity' => 10,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $courseWithStudents->id,
            'capacity' => 10,
        ]);
    }

    public function test_他の講師の講座は影響を受けないこと(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();

        Course::factory()->count(2)->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 20,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('courses', [
            'id' => $otherCourse->id,
            'capacity' => 20,
        ]);
    }

    public function test_論理削除された講座は対象外であること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $activeCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $deletedCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $deletedCourse->delete();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $activeCourse->id,
            'capacity' => null,
        ]);
    }

    public function test_未認証の場合はエラーになること(): void
    {
        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear-all'));

        // Assert
        $response->assertStatus(401);
    }
}
