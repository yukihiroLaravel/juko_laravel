<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-PROG-002 */
    public function test_下書きから公開への変更で受講状況が生成される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lessons.update-status', [
            'lesson_id' => $lesson->id,
        ]), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'public',
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
    }

    public function test_下書きから非公開への変更はできない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lessons.update-status', [
            'lesson_id' => $lesson->id,
        ]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'draft',
        ]);
    }

    /** AC-PROG-002 */
    public function test_公開のレッスンを公開に更新しても受講状況は生成されない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'public']);
        Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lessons.update-status', [
            'lesson_id' => $lesson->id,
        ]), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('lesson_attendances', 0);
    }

    /** AC-AUTHZ-004 */
    public function test_他の講師のレッスンの公開状態は変更できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lessons.update-status', [
            'lesson_id' => $lesson->id,
        ]), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'draft',
        ]);
    }

    public function test_公開でも非公開でもない公開状態は指定できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lessons.update-status', [
            'lesson_id' => $lesson->id,
        ]), [
            'status' => 'invalid',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
