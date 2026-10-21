<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClearCapacityTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ENROLL-021 */
    public function test_選択した講座の定員をなくせること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $courses = Course::factory()->count(3)->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => $courses->pluck('id')->toArray(),
        ]);

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

    /** AC-ENROLL-020 */
    public function test_受講者が存在する講座が含まれている場合はエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

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
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$courseWithoutStudents->id, $courseWithStudents->id],
        ]);

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

    /** AC-AUTHZ-002 */
    public function test_マネージャーが自分の講座の定員をクリアできること(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => null,
        ]);
    }

    /** AC-AUTHZ-002 */
    public function test_マネージャーが配下の講師の講座の定員をクリアできること(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $course = Course::factory()->create([
            'instructor_id' => $subordinate->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => null,
        ]);
    }

    /** AC-AUTHZ-001, AC-AUTHZ-003 */
    #[DataProvider('instructorTypeProvider')]
    public function test_担当でも配下でもない講師の講座の定員は取り消せない(string $type): void
    {
        // Arrange — 操作者の担当でも配下でもない講師の講座
        $operator = Instructor::factory()->create(['type' => $type]);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 10,
        ]);
        $this->actingAs($operator, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
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
    public function test_一部に権限がない講座を含む場合変更できないこと(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'capacity' => 10,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $ownCourse->id,
            'capacity' => 10,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidCoursesProvider')]
    public function test_講座の指定が不正だと定員を取り消せない(array $payload, string $errorKey): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), $payload);

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
            '講座の指定が配列でない' => [['courses' => 'abc'], 'courses'],
            '講座の指定が整数でない' => [['courses' => ['abc']], 'courses.0'],
            '存在しない講座を含む' => [['courses' => [99999]], 'courses.0'],
        ];
    }

    public function test_削除済みの講座を含むと定員を取り消せない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $course->delete();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_未認証の場合はエラーになること(): void
    {
        // Arrange
        $course = Course::factory()->create(['capacity' => 10]);

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
