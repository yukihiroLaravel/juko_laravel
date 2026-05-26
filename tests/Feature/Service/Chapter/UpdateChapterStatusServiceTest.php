<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Services\Chapter\UpdateChapterStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateChapterStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプターの公開状態を更新_成功(): void
    {
        // Arrange
        $service = new UpdateChapterStatusService;

        $chapters = Chapter::factory()->create([
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Act
        $service(collect($chapters), 'private');

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapters->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    public function test_チャプターの公開状態を更新_失敗(): void
    {
        // Arrange
        $service = new UpdateChapterStatusService;

        $chapters = Chapter::factory()->create([
            'status' => StatusEnum::DRAFT->value,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            StatusEnum::DRAFT->value.'は、'.StatusEnum::PRIVATE->value.'に変更できません。'
        );

        // Act
        $service(collect($chapters), 'private');

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapters->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }
}
