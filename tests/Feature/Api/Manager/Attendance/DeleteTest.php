<?php

namespace Tests\Feature\Api\Manager\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講削除_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.attendance.delete', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        $this->assertSoftDeleted('attendances', [
            'id' => $attendance->id,
        ]);
        $this->assertSoftDeleted('lesson_attendances', [
            'attendance_id' => $attendance->id,
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 別のマネージャーの講座の受講を削除しようとする
        $ownerManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerManager->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.attendance.delete', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.attendance.delete', ['attendance_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }
}
