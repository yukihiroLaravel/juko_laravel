<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Services\Chapter\UpdateAllChaptersStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAllChaptersStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_全チャプターの公開状態を更新_成功(): void
    {
        // Arrange
        $service = new UpdateAllChaptersStatusService;

        $course = Course::factory()->create();
        $chapters = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Act
        $service($course->id, 'private');

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapters->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    public function test_全チャプターの公開状態を更新_失敗(): void
    {
        // Arrange
        $service = new UpdateAllChaptersStatusService;

        $course = Course::factory()->create();
        $chapters = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::DRAFT->value,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            StatusEnum::DRAFT->value.'は、'.StatusEnum::PRIVATE->value.'に変更できません。'
        );

        // Act
        $service($course->id, 'private');

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapters->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }
}