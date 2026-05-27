<?php

namespace Tests\Feature\Api\Manager\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプターの公開状態を更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapter.update-status', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]), [
            'chapters' => [$chapter->id],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    public function test_チャプターの公開状態を更新_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapter.update-status', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]), [
            'chapters' => [$chapter->id],
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => [
            'ステータスを「'.StatusEnum::DRAFT->value.'」から「'.StatusEnum::PRIVATE->value.'」へ変更することはできません。',
        ]]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    public function test_バリデーションエラー_statusが下書き(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapter.update-status', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]), [
            'chapters' => [$chapter->id],
            'status' => StatusEnum::DRAFT->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが許容外の文字列(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapter.update-status', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]), [
            'chapters' => [$chapter->id],
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
