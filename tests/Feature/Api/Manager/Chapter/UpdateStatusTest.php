<?php

namespace Tests\Feature\Api\Manager\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-022 */
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
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
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

    /** AC-AUTHZ-002 */
    public function test_配下の講師のチャプターの公開状態をマネージャーが更新できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    /** AC-AUTHZ-003 */
    public function test_配下でないマネージャーのチャプターの公開状態は更新できない(): void
    {
        // Arrange — 別のマネージャーの講座のチャプター
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    /** AC-AUTHOR-009 */
    public function test_下書きのチャプターは非公開にできない(): void
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
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
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

    /**
     * 公開状態に指定できない値
     *
     * @return array<string, array{string}>
     */
    public static function invalidStatuses(): array
    {
        return [
            '下書き' => [StatusEnum::DRAFT->value],
            '存在しない値' => ['invalid_status'],
        ];
    }

    #[DataProvider('invalidStatuses')]
    public function test_公開状態に下書きや存在しない値は指定できない(string $status): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'status' => $status,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_チャプターの識別子が数値でないと更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => 'aaa',
        ]), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['chapter_id']);
    }

    public function test_削除済みのチャプターは更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $chapter->delete();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('manager.chapters.update-status', [
            'chapter_id' => $chapter->id,
        ]), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['chapter_id']);
    }
}
