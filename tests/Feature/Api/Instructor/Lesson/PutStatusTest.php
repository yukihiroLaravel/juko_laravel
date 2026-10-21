<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-029 */
    public function test_複数のレッスンを非公開にできる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'public']);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.lessons.put-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson1->id,
                $lesson2->id,
            ],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson1->id,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson2->id,
            'status' => 'private',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_権限がない講師のレッスン公開状態の変更_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.lessons.put-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson1->id,
                $lesson2->id,
            ],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_レッスンの指定が空で公開状態が不正だと変更できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.lessons.put-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [],
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons',
            'status',
        ]);
    }
}
