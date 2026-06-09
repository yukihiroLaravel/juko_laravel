<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_レッスン登録_成功(): void
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

    public function test_受講者がいる講座でレッスンを作成しても受講状況は作成されない(): void
    {
        // Arrange — すでに受講者がいる講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.store', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => 'title',
        ]);

        // Assert — 作成したレッスンに対する受講状況は作られない
        $response->assertStatus(200);
        $this->assertDatabaseMissing('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $response->json('lesson_id'),
        ]);
    }

    public function test_権限がない講師のレッスン登録_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create();
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

    public function test_バリデーションエラー(): void
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
