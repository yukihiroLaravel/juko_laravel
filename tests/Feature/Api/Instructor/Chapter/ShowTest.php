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

    public function test_チャプター取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapter.show', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_講師が一致しない_失敗(): void
    {
        // Arrange — 別の講師の講座のチャプター
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapter.show', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座が一致しない_失敗(): void
    {
        // Arrange — チャプターが属する講座と異なるcourse_idを指定
        $instructor = Instructor::factory()->create();
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course1->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapter.show', [
            'course_id' => $course2->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid course_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.chapter.show', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
        ]);
    }
}
