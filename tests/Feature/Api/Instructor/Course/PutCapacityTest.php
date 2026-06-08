<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座定員を一括更新_成功(): void
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

    public function test_定員をnullに更新_成功(): void
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

    public function test_マネージャーで配下の講師の講座を一括更新_成功(): void
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

    public function test_定員が現在の受講者数を下回る場合_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 10]);
        $students = Student::factory()->count(5)->create();
        foreach ($students as $student) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        }
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 3,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    public function test_権限がないマネージャーの場合更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($manager, 'instructor');

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

    public function test_権限がない講師の場合更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

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

    public function test_バリデーションエラー_coursesが未送信(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが空配列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが配列でない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => 'abc',
            'capacity' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_courses要素が整数でない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => ['abc'],
            'capacity' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_存在しない_i_dを含む(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [99999],
            'capacity' => 10,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_削除済みの_i_dを含む(): void
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

    public function test_バリデーションエラー_capacityが0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 0,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    public function test_バリデーションエラー_capacityが負の値(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => -1,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    public function test_バリデーションエラー_capacityが文字列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-capacity'), [
            'courses' => [$course->id],
            'capacity' => 'abc',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }
}
