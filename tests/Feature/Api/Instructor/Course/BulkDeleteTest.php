<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-018, AC-AUTHZ-002 */
    public function test_マネージャーは自分と配下の講師の講座をまとめて削除できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course1 = Course::factory()->create(['instructor_id' => $manager->id]);
        $course2 = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $course3 = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course1->id, $course2->id, $course3->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 3,
        ]);
        $this->assertSoftDeleted('courses', ['id' => $course1->id]);
        $this->assertSoftDeleted('courses', ['id' => $course2->id]);
        $this->assertSoftDeleted('courses', ['id' => $course3->id]);
    }

    /** AC-AUTHOR-018, AC-AUTHZ-001 */
    public function test_講師は自分の講座をまとめて削除できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course1->id, $course2->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 2,
        ]);
        $this->assertSoftDeleted('courses', ['id' => $course1->id]);
        $this->assertSoftDeleted('courses', ['id' => $course2->id]);
    }

    /** AC-AUTHZ-001, AC-AUTHZ-003 */
    #[DataProvider('instructorTypeProvider')]
    public function test_担当でも配下でもない講師の講座は削除できない(string $type): void
    {
        // Arrange — 操作者の担当でも配下でもない講師の講座
        $operator = Instructor::factory()->create(['type' => $type]);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($operator, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
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
    public function test_一部に権限がない講座を含む場合削除できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $ownCourse->id,
            'deleted_at' => null,
        ]);
    }

    /** AC-AUTHOR-018 */
    public function test_受講生がいる講座は削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
    }

    /** AC-AUTHOR-018 */
    public function test_一部に受講生がいる講座を含む場合削除できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $courseWithoutStudents = Course::factory()->create(['instructor_id' => $manager->id]);
        $courseWithStudents = Course::factory()->create(['instructor_id' => $manager->id]);
        Attendance::factory()->create(['course_id' => $courseWithStudents->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$courseWithoutStudents->id, $courseWithStudents->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $courseWithoutStudents->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidCoursesProvider')]
    public function test_講座の指定が不正だと削除できない(array $payload, string $errorKey): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), $payload);

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

    public function test_削除済みの講座を含むと削除できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->delete();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }
}
