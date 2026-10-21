<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-031 */
    public function test_複数レッスンの一括削除_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $lesson3 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 3]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.bulk-delete', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [$lesson1->id, $lesson2->id],
        ]);

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
        // 残ったレッスンは先頭に繰り上がる
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lesson3->id,
            'order' => 1,
        ]);
    }

    /** AC-AUTHOR-030 AC-COMMON-014 */
    public function test_受講済みレッスンを含む一括削除_失敗(): void
    {
        // Arrange — 削除対象の一方にだけ受講がある
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessonWithAttendance = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lessonWithoutAttendance = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lessonWithAttendance->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.bulk-delete', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [$lessonWithAttendance->id, $lessonWithoutAttendance->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this lesson has attendance.',
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

    /** AC-AUTHZ-012 */
    public function test_別チャプターのレッスンを含む一括削除_失敗(): void
    {
        // Arrange — 同じ講師の別チャプターに属するレッスンを混ぜる
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherChapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $otherChapterLesson = Lesson::factory()->create(['chapter_id' => $otherChapter->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.bulk-delete', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [$lesson->id, $otherChapterLesson->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lesson->id,
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $otherChapterLesson->id,
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_権限がない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.bulk-delete', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [$lesson->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $lesson->id,
        ]);
    }

    public function test_レッスンを1件も指定しないと削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.lessons.bulk-delete', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons',
        ]);
    }
}
