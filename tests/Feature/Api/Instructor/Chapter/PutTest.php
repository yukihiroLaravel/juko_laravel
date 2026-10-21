<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-020 */
    public function test_チャプターの名称を更新できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => '更新テスト',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'title' => '更新テスト',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターは更新できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put', [
            'chapter_id' => $chapter->id,
        ]), [
            'title' => '更新テスト',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_チャプターの指定が数値でない場合や名称が空の場合は更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put', [
            'chapter_id' => 'bbb',
        ]), [
            'title' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
            'title',
        ]);
    }
}
