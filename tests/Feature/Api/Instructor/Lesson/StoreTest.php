<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
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
        $response = $this->postJson(route('instructor.lesson.store', [
            'course_id' => $course->id,
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
        $response = $this->postJson(route('instructor.lesson.store', [
            'course_id' => $course->id,
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
        $response = $this->postJson(route('instructor.lesson.store', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
        ]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'title',
        ]);
    }
}
