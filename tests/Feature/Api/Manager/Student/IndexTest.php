<?php

namespace Tests\Feature\Api\Manager\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分の講座の受講生を一覧で取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.students.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.students');
        $response->assertJsonPath('data.students.0.student_id', $student->id);
    }

    public function test_講座を指定すると指定した講座の受講生だけを取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $otherStudent->id,
            'course_id' => $otherCourse->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.students.index', ['courses' => [$course->id]]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.students');
        $response->assertJsonPath('data.students.0.student_id', $student->id);
    }

    public function test_削除済みの講座を指定すると入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->delete();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.students.index', ['courses' => [$course->id]]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_配下でない講師の講座を指定すると受講生を取得できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherManager = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.students.index', ['courses' => [$otherCourse->id]]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid course_id.',
        ]);
    }

    public function test_講座の指定が整数でないときは入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.students.index', ['courses' => ['aaa']]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'courses.0',
        ]);
    }
}
