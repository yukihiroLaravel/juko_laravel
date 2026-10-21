<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAllTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-024 */
    public function test_講座の全チャプターとそのレッスンを削除できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapters = Chapter::factory()->count(2)->create(['course_id' => $course->id]);
        foreach ($chapters as $chapter) {
            Lesson::factory()->create(['chapter_id' => $chapter->id]);
        }
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.delete-all', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        foreach ($chapters as $chapter) {
            $this->assertSoftDeleted('chapters', [
                'id' => $chapter->id,
            ]);
            $this->assertSoftDeleted('lessons', [
                'chapter_id' => $chapter->id,
            ]);
        }
    }

    /** AC-AUTHOR-023 */
    public function test_受講状況があるレッスンを含むチャプターがあると全チャプターを削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.delete-all', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this chapter has lessons with attendance.',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターは全削除できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.delete-all', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座の指定が数値でない場合は全削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.delete-all', ['course_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
        ]);
    }
}
