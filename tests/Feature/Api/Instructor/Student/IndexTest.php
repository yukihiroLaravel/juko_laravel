<?php

namespace Tests\Feature\Api\Instructor\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_講師の講座を受講している受講生の一覧を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.students.0.student_id', $attendance->student_id);
    }

    public function test_削除済みの講座を指定して絞り込めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.index').'?courses[]='.$course->id);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'courses.0',
        ]);
    }

    public function test_他の講師の講座を指定して絞り込めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.index').'?courses[]='.$otherCourse->id);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座の指定が数値でないときは絞り込めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.students.index').'?courses[]=aaa');

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'courses.0',
        ]);
    }
}
