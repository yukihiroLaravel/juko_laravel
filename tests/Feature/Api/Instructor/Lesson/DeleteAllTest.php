<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプター内の全レッスン削除_成功(): void
    {
        // Arrange — 受講なしのレッスンと、別チャプターのレッスンを用意する
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $otherChapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherChapterLesson = Lesson::factory()->create(['chapter_id' => $otherChapter->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.delete-all', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertSoftDeleted('lessons', [
            'id' => $lesson1->id,
            'order' => 0,
        ]);
        $this->assertSoftDeleted('lessons', [
            'id' => $lesson2->id,
            'order' => 0,
        ]);
        // 別チャプターのレッスンは削除されない
        $this->assertNotSoftDeleted('lessons', [
            'id' => $otherChapterLesson->id,
            'order' => 1,
        ]);
    }

    public function test_受講済みレッスンを含むチャプターの全レッスン削除_失敗(): void
    {
        // Arrange — チャプター内の一方のレッスンにだけ受講がある
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessonWithAttendance = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lessonWithoutAttendance = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lessonWithAttendance->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.delete-all', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, these lessons have attendance.',
        ]);
        // 受講のないレッスンも巻き込まれずに残る
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lessonWithAttendance->id,
            'order' => 1,
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lessonWithoutAttendance->id,
            'order' => 2,
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.delete-all', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lesson->id,
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.delete-all', [
            'chapter_id' => 'ccc',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
        ]);
    }
}
