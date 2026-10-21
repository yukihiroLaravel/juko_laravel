<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('instructorTypeProvider')]
    public function test_講師の講座を受講している受講生の情報を取得できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create(['last_login_at' => now()]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.show', ['student_id' => $student->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.student_id', $student->id);
        $response->assertJsonPath('data.email', $student->email);
    }

    public function test_受講していない講師は受講生の情報を取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.show', ['student_id' => $student->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講生の指定が数値でないときは取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.show', ['student_id' => 'bbb']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id',
        ]);
    }

    public function test_一度もログインしていない受講生は最終ログイン日時なしで取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create(['last_login_at' => null]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.show', ['student_id' => $student->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.last_login_at', null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instructorTypeProvider(): array
    {
        return [
            'マネージャー' => ['manager'],
            '講師' => ['instructor'],
        ];
    }
}
