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

    public function test_受講生一覧取得_成功(): void
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
    }

    public function test_講座id指定_受講生一覧取得_成功(): void
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
        $response = $this->getJson(route('manager.students.index', ['courses' => [$course->id]]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_講座id指定_講師が一致しない_失敗(): void
    {
        // Arrange — 別のマネージャーの講座IDを指定
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

    public function test_バリデーションエラー(): void
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
