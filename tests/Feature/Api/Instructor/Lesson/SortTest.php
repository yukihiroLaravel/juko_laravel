<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortTest extends TestCase
{
    use RefreshDatabase;

    public function test_レッスン並び替え_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $lesson3 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 3]);
        $lesson4 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 4]);
        $this->actingAs($instructor, 'instructor');

        // Act — 逆順に並び替え
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson4->id,
                $lesson3->id,
                $lesson2->id,
                $lesson1->id,
            ],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson4->id, 'order' => 1]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson3->id, 'order' => 2]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson2->id, 'order' => 3]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson1->id, 'order' => 4]);
    }

    public function test_権限がない講師のレッスン登録_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson1->id,
            ],
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
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
            'lessons',
        ]);
    }

    public function test_レッスン_i_dが不足している_失敗(): void
    {
        // Arrange — チャプターに4つのレッスンがあるが3つだけ指定
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $lesson3 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 3]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 4]); // 含めない
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson3->id,
                $lesson2->id,
                $lesson1->id,
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons' => 'all valid lessons not found for the specified chapter.',
        ]);
    }

    public function test_余計なレッスンが含まれている_失敗(): void
    {
        // Arrange — 別のチャプターのレッスンを含める
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 2]);
        $otherLesson = Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => $chapter1->id,
        ]), [
            'lessons' => [
                $lesson2->id,
                $otherLesson->id,
                $lesson1->id,
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons' => 'invalid lessons found for the specified chapter.',
        ]);
    }

    public function test_レッスンが重複している_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.chapters.lessons.sort', [
            'chapter_id' => $chapter->id,
        ]), [
            'lessons' => [
                $lesson1->id,
                $lesson2->id,
                $lesson1->id, // 重複
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lessons' => 'duplicate lessons found.',
        ]);
    }
}
