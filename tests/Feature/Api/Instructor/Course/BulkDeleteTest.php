<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーで自分の講座を一括削除する(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 1,
        ]);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_マネージャーで配下の講師の講座を一括削除する(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 1,
        ]);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_マネージャーで複数講座を一括削除する(): void
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

    public function test_講師で自分の講座を一括削除する(): void
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

    public function test_権限がないマネージャーの場合削除できない(): void
    {
        // Arrange — 配下にない講師の講座
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($manager, 'instructor');

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

    public function test_権限がない講師の場合削除できない(): void
    {
        // Arrange — 別の講師の講座
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

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

    public function test_受講生がいる講座は削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
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

    public function test_一部に受講生がいる講座を含む場合削除できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $courseWithoutStudents = Course::factory()->create(['instructor_id' => $manager->id]);
        $courseWithStudents = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseWithStudents->id,
        ]);
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

    public function test_バリデーションエラー_coursesが未送信(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが空配列(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが配列でない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => 'abc',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_courses要素が整数でない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => ['abc'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_存在しない_i_dを含む(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.bulk-delete'), [
            'courses' => [99999],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_削除済みの_i_dを含む(): void
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
