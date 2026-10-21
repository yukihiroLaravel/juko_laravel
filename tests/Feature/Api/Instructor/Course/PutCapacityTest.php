<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_選択した講座の定員をまとめて変更できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 10]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 20]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course1->id, $course2->id],
            'capacity' => 50,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', ['id' => $course1->id, 'capacity' => 50]);
        $this->assertDatabaseHas('courses', ['id' => $course2->id, 'capacity' => 50]);
    }

    /** AC-ENROLL-019 */
    public function test_定員を空にすると定員なしになる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 10]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => null,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'capacity' => null]);
    }

    /** AC-AUTHZ-002 */
    public function test_マネージャーは配下の講師の講座の定員を変更できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id, 'capacity' => 10]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'capacity' => 30]);
    }

    /** AC-ENROLL-018 */
    public function test_定員が現在の受講者数を下回る講座を含むと1件も更新されない(): void
    {
        // Arrange — 受講者5人の講座と、受講者のいない講座
        $instructor = Instructor::factory()->create();
        $courseWithStudents = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 10]);
        $courseWithoutStudents = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 10]);
        Attendance::factory()->count(5)->create(['course_id' => $courseWithStudents->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$courseWithStudents->id, $courseWithoutStudents->id],
            'capacity' => 3,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
        $this->assertDatabaseHas('courses', ['id' => $courseWithStudents->id, 'capacity' => 10]);
        $this->assertDatabaseHas('courses', ['id' => $courseWithoutStudents->id, 'capacity' => 10]);
    }

    /** AC-AUTHZ-001, AC-AUTHZ-003 */
    #[DataProvider('instructorTypeProvider')]
    public function test_担当でも配下でもない講師の講座の定員は変更できない(string $type): void
    {
        // Arrange — 操作者の担当でも配下でもない講師の講座
        $operator = Instructor::factory()->create(['type' => $type]);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($operator, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 50,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
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

    /** AC-AUTHZ-003 */
    public function test_一部に権限がない講座を含む場合更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create(['instructor_id' => $manager->id, 'capacity' => 10]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id, 'capacity' => 10]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
            'capacity' => 50,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertDatabaseHas('courses', ['id' => $ownCourse->id, 'capacity' => 10]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidCoursesProvider')]
    public function test_講座の指定が不正だと定員を変更できない(array $payload, string $errorKey): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$errorKey]);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidCoursesProvider(): array
    {
        return [
            '講座の指定がない' => [[], 'courses'],
            '講座の指定が空' => [['courses' => []], 'courses'],
            '講座の指定が配列でない' => [['courses' => 'abc', 'capacity' => 10], 'courses'],
            '講座の指定が整数でない' => [['courses' => ['abc'], 'capacity' => 10], 'courses.0'],
            '存在しない講座を含む' => [['courses' => [99999], 'capacity' => 10], 'courses.0'],
        ];
    }

    public function test_削除済みの講座を含むと定員を変更できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    #[DataProvider('invalidCapacityProvider')]
    public function test_定員が不正だと変更できない(mixed $capacity): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => $capacity,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidCapacityProvider(): array
    {
        return [
            '定員が0' => [0],
            '定員が負の数' => [-1],
            '定員が文字列' => ['abc'],
        ];
    }
}
