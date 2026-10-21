<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-025 */
    public function test_チャプターにレッスンを下書きで登録できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.store', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => 'title',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
            'lesson_id',
        ]);
        $this->assertDatabaseHas('lessons', [
            'chapter_id' => $chapter->id,
            'title' => 'title',
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
    }

    /** AC-AUTHOR-025, AC-PROG-001 */
    public function test_受講生がいる講座でレッスンを作成しても受講状況は作成されない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.store', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => 'title',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $response->json('lesson_id'),
        ]);
    }

    /** AC-AUTHZ-004 */
    public function test_他の講師のチャプターにはレッスンを登録できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.store', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => 'title',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_チャプターの指定が不正でタイトルが空のときはレッスンを登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.store', [
            'chapter_id' => 'bbb',
        ]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
            'title',
        ]);
    }
}
