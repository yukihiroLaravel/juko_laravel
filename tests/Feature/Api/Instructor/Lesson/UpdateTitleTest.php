<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_レッスンタイトル更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lesson.update-title', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => '新しいタイトル',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => '新しいタイトル',
        ]);
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.lesson.update-title', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => '新しいタイトル',
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
        $response = $this->patchJson(route('instructor.lesson.update-title', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
            'lesson_id' => 'ccc',
        ]), [
            'title' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'The title field is required.',
            'course_id' => 'The course id must be an integer.',
            'chapter_id' => 'The chapter id must be an integer.',
            'lesson_id' => 'The lesson id must be an integer.',
        ]);
    }
}
