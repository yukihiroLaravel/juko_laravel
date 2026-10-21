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

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-024 */
    public function test_選んだチャプターとそのレッスンだけをまとめて削除できる(): void
    {
        // Arrange — 選ぶチャプターと、選ばないチャプターを用意する
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $unselectedChapter = Chapter::factory()->create(['course_id' => $course->id]);
        $unselectedLesson = Lesson::factory()->create(['chapter_id' => $unselectedChapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => $course->id]), [
            'chapters' => [$chapter->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertSoftDeleted('chapters', [
            'id' => $chapter->id,
        ]);
        $this->assertSoftDeleted('lessons', [
            'chapter_id' => $chapter->id,
        ]);
        $this->assertNotSoftDeleted('chapters', [
            'id' => $unselectedChapter->id,
        ]);
        $this->assertNotSoftDeleted('lessons', [
            'id' => $unselectedLesson->id,
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターは削除できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => $course->id]), [
            'chapters' => [$chapter->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /** AC-AUTHZ-011 */
    public function test_指定した講座に属さないチャプターは削除できない(): void
    {
        // Arrange — チャプターが属する講座とは別の講座を指定する
        $instructor = Instructor::factory()->create();
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course1->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => $course2->id]), [
            'chapters' => [$chapter->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid course_id.',
        ]);
    }

    /** AC-AUTHOR-023 */
    public function test_受講状況があるレッスンを含むチャプターは削除できない(): void
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
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => $course->id]), [
            'chapters' => [$chapter->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this chapter has lessons with attendance.',
        ]);
    }

    public function test_講座とチャプターの指定が不正な場合は削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => 'aaa']), [
            'chapters' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapters',
        ]);
    }

    /** AC-COMMON-015 */
    public function test_削除の途中で失敗するとレッスンも元のまま残る(): void
    {
        // Arrange — レッスンを消したあと、チャプターを消すクエリの直後に失敗させる
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->failAfterQuery('update "chapters"');
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.chapters.bulk-delete', ['course_id' => $course->id]), [
            'chapters' => [$chapter->id],
        ]);

        // Assert
        $response->assertStatus(500);
        $this->assertNotSoftDeleted('chapters', ['id' => $chapter->id]);
        $this->assertNotSoftDeleted('lessons', ['id' => $lesson->id]);
    }
}
