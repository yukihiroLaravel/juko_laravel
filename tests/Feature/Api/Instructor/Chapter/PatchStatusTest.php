<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatchStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-022 */
    public function test_選んだチャプターの公開状態をまとめて変更できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'status' => 'public']);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'status' => 'public']);
        $unselectedChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.chapters.patch-status', ['course_id' => $course->id]), [
            'chapters' => [$chapter1->id, $chapter2->id],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter1->id,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter2->id,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $unselectedChapter->id,
            'status' => 'public',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターの公開状態は変更できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.chapters.patch-status', ['course_id' => $course->id]), [
            'chapters' => [$chapter1->id, $chapter2->id],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /** AC-AUTHZ-011 */
    public function test_指定した講座に属さないチャプターの公開状態は変更できない(): void
    {
        // Arrange — チャプターが属する講座とは別の講座を指定する
        $instructor = Instructor::factory()->create();
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course1->id]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course1->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.chapters.patch-status', ['course_id' => $course2->id]), [
            'chapters' => [$chapter1->id, $chapter2->id],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座とチャプターと公開状態の指定が不正な場合は変更できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.chapters.patch-status', ['course_id' => 'aaa']), [
            'chapters' => 'string',
            'status' => 'string',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapters',
            'status',
        ]);
    }

    public function test_公開状態に下書きは指定できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.chapters.patch-status', ['course_id' => $course->id]), [
            'chapters' => [$chapter1->id],
            'status' => 'draft',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
