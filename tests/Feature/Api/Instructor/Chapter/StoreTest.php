<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-019 */
    public function test_チャプターは下書きで講座の末尾に登録される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.store', ['course_id' => $course->id]), [
            'title' => 'title',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
            'chapter_id',
        ]);
        $this->assertDatabaseHas('chapters', [
            'course_id' => $course->id,
            'title' => 'title',
            'order' => 2,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    /** AC-AUTHZ-003, AC-AUTHZ-004 */
    public function test_他の講師の講座にはチャプターを登録できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.store', ['course_id' => $course->id]), [
            'title' => 'title',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座の指定が不正でチャプター名が空だと登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.store', ['course_id' => 'aaa']), [
            'title' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
        ]);
    }
}
