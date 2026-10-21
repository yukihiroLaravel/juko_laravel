<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-022 */
    public function test_講座の全チャプターの公開状態をまとめて変更できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapters = Chapter::factory()->count(2)->create(['course_id' => $course->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        foreach ($chapters as $chapter) {
            $this->assertDatabaseHas('chapters', [
                'id' => $chapter->id,
                'status' => 'private',
            ]);
        }
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターの公開状態は変更できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座と公開状態の指定が不正な場合は変更できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => 'aaa']), [
            'status' => 'string',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'status',
        ]);
    }

    public function test_公開状態に下書きは指定できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'draft',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
