<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプター登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
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
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 別の講師の講座にチャプターを作成
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

    public function test_バリデーションエラー(): void
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
