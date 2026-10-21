<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{int|null}>
     */
    public static function capacityPatterns(): array
    {
        return [
            '定員に空きがある' => [10],
            '定員なし' => [null],
        ];
    }

    /** AC-ENROLL-008 */
    #[DataProvider('capacityPatterns')]
    public function test_講師が受講生を新たに用意して講座に受講登録できる(?int $capacity): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => $capacity,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.students.store'), [
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

    /** AC-ENROLL-003 */
    public function test_定員に達している講座には受講登録できない(): void
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
        $response = $this->postJson(route('instructor.students.store'), [
            'given_name_by_instructor' => 'Over',
            'email' => 'over@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_他の講師の講座には受講登録できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.students.store'), [
            'given_name_by_instructor' => 'Unauthorized',
            'email' => 'unauthorized@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_削除済みの講座には受講登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.students.store'), [
            'given_name_by_instructor' => 'Deleted',
            'email' => 'deleted@example.com',
            'course_id' => $course->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_入力がすべて空のときは受講登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.students.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'given_name_by_instructor',
            'email',
            'course_id',
        ]);
    }
}
