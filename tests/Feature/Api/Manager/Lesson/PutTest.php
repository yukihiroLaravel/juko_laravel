<?php

namespace Tests\Feature\Api\Manager\Lesson;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーのレッスン更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
        ]);
    }

    public function test_配下の講師のレッスン更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
    }

    public function test_権限がない講師のレッスン更新_失敗(): void
    {
        // Arrange — 別のマネージャーの講座のレッスンを更新しようとする
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーではない講師のレッスン登録_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.lesson.put', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
            'lesson_id' => $lesson->id,
        ]), [
            'title' => 'title',
            'url' => 'url',
            'remarks' => 'remarks',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.lesson.put', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
            'lesson_id' => 'ccc',
        ]), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
            'lesson_id',
            'title',
            'url',
            'status',
        ]);
    }
}
