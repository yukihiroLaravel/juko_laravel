<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーで講座削除する(): void
    {
        // Arrange — マネージャー自身の講座（受講生なし）
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_マネージャーで配下の講師の講座削除する(): void
    {
        // Arrange — 配下の講師の講座を削除
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_講師で講座削除する(): void
    {
        // Arrange — 講師自身の講座（受講生なし、受講期限あり）
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        CourseDeadline::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
    }

    public function test_権限がないマネージャーの場合削除できない(): void
    {
        // Arrange — 配下にない講師の講座
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

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
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講生がいる講座は削除できない(): void
    {
        // Arrange — 受講生がいる講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.course.delete', ['course_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
