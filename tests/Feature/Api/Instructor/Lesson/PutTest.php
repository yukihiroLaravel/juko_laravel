<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    public function test_レッスン更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'chapter_id' => $chapter->id,
            'title' => 'title',
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
        $response = $this->putJson(route('instructor.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
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
        $response = $this->putJson(route('instructor.lesson.put', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
            'lesson_id' => 'ccc',
        ]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'lesson_id',
            'title',
            'url',
            'status',
        ]);
    }
}
