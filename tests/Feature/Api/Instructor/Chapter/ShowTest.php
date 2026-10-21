<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプターを取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapters.show', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-003, AC-AUTHZ-004 */
    public function test_他の講師の講座のチャプターは取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapters.show', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_チャプターの指定が数値でないと取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapters.show', [
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
        ]);
    }
}
